<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AppointmentController extends Controller
{
    /**
     * Show the appointment list using the signed-in user's role to limit its records.
     * Students see only their assigned appointments, teachers see appointments for
     * their assigned students, and administrators can browse the full list.
     */
    public function index(Request $request): View
    {
        $user = auth()->user();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Appointment::STATUSES)],
        ]);

        $query = Appointment::with(['student', 'assignedPersonnel', 'createdBy'])->latest();

        // Scope the query before pagination so another role's appointments are never sent to the view.
        if ($user->role === 'student') {
            $query->where('student_id', $user->id);
        } elseif ($user->role === 'teacher') {
            $query->whereHas('student', fn ($students) => $students->where('assigned_teacher_id', $user->id));
        }

        if ($user->role === 'student') {
            // Hide unassigned student requests; staff can still see and schedule these requests.
            $query->whereNotNull('scheduled_at');
        } else {
            if ($status = $filters['status'] ?? null) {
                $query->where('status', $status);
            }

            $search = trim((string) ($filters['search'] ?? ''));
        }

        // Search is for staff only. Students cannot search for other students or their requests.
        if ($user->role !== 'student' && $search !== '') {
            $query->where(function ($builder) use ($search, $user) {
                $builder->where('title', 'like', "%{$search}%");
                if ($user->role !== 'student') {
                    $builder->orWhereHas('student', fn ($students) => $students->where('name', 'like', "%{$search}%"));
                }
            });
        }

        // Keep search and status filters when the user moves between result pages.
        $appointments = $query->paginate(15)->withQueryString();

        $students = in_array($user->role, ['admin', 'teacher'], true)
            ? \App\Models\User::where('role', 'student')
                ->when($user->role === 'teacher', fn ($query) => $query->where('assigned_teacher_id', $user->id))
                ->orderBy('name')->get(['id', 'name', 'email'])
            : collect();
        return view('appointments.index', compact('appointments', 'students'));
    }

    public function availableTimes(Request $request): JsonResponse|RedirectResponse
    {
        // Only staff schedule consultations; students do not receive the list of open slots.
        abort_unless(in_array($request->user()->role, ['admin', 'teacher'], true), 403);

        // The appointment form calls this endpoint with JavaScript and expects JSON, not a page.
        if (! $request->expectsJson()) {
            return redirect()->route('appointments.index');
        }

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(30)->toDateString()],
            'appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')],
        ]);

        // If an appointment ID is supplied, exclude that booking while assembling open slots.
        $except = isset($validated['appointment_id']) ? Appointment::findOrFail($validated['appointment_id']) : null;
        $times = collect($this->availableSlots($except))
            ->where('date', $validated['date'])
            ->map(fn ($slot) => ['value' => $slot['time'], 'label' => $slot['time_label']])
            ->values();

        return response()->json(['times' => $times]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Students submit requests through the student-facing workflow; staff may create one for them.
        abort_unless(
            in_array($user->role, ['admin', 'teacher'], true),
            403,
            'Only teachers or administrators can create consultation requests.'
        );

        // A teacher can create requests only for students assigned to that teacher.
        // An administrator may create a request for any student account.
        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('users', 'id')->where(function ($query) use ($user) {
                $query->where('role', 'student');
                if ($user->role === 'teacher') {
                    $query->where('assigned_teacher_id', $user->id);
                }
            })],
            'title' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
        ]);
        // Teachers must schedule the request immediately; admins may leave it unassigned for later.
        $scheduledAt = $this->selectedSchedule($request, $user->role === 'teacher');

        // Every new request starts pending and uses the application's standard 30-minute duration.
        Appointment::create([
            'student_id' => $validated['student_id'],
            'created_by' => $user->id,
            'title' => $validated['title'],
            'reason' => $validated['reason'] ?? null,
            'scheduled_at' => $scheduledAt,
            'duration_minutes' => 30,
            'status' => 'pending',
        ]);

        return redirect()
            ->route('appointments.index')
            ->with('success', 'Consultation request submitted successfully.');
    }

    public function edit(Appointment $appointment): View
    {
        // Check ownership/assignment before loading the edit page, then restrict editing to staff.
        $this->authorizeAppointment($appointment);
        $isScheduler = in_array(auth()->user()->role, ['admin', 'teacher'], true);
        abort_unless($isScheduler, 403);

        $personnel = auth()->user()->role === 'admin'
            ? \App\Models\User::where('role', 'admin')->orderBy('name')->get(['id', 'name'])
            : collect();
        return view('appointments.edit', compact('appointment', 'personnel'));
    }

    public function update(Request $request, Appointment $appointment): RedirectResponse
    {
        // Role-specific updates are checked on the server even if the page hides certain form fields.
        $this->authorizeAppointment($appointment);
        $user = $request->user();

        // Admins can manage the workflow status and private notes. A request can only be approved
        // after it has a valid open time slot.
        if ($user->role === 'admin') {
            $validated = $request->validate([
                'status' => ['required', Rule::in(Appointment::STATUSES)],
                'assigned_to' => ['nullable', 'integer', Rule::exists('users', 'id')->where('role', 'admin')],
                'notes' => ['nullable', 'string', 'max:2000'],
            ]);
            $scheduledAt = $this->selectedSchedule($request, false, $appointment);

            if ($validated['status'] === 'approved' && empty($scheduledAt)) {
                return back()->withErrors(['scheduled_date' => 'Assign an available consultation time before approving this request.'])->withInput();
            }

            $appointment->update([...$validated, 'scheduled_at' => $scheduledAt]);
        } elseif ($user->role === 'teacher') {
            // Teachers only assign times to pending requests; they cannot approve or complete them.
            abort_unless($appointment->status === 'pending', 403, 'Teachers can assign times to pending requests only.');
            $scheduledAt = $this->selectedSchedule($request, false, $appointment);
            $appointment->update(['scheduled_at' => $scheduledAt]);
        } else {
            // Students may view assigned schedule details, but cannot edit them.
            abort(403, 'Students can only view consultation dates and times assigned to them.');
        }

        return redirect()->route('appointments.index')->with('success', 'Appointment updated successfully.');
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        $this->authorizeAppointment($appointment);
        // The cancel action changes workflow status, so it is reserved for administrators.
        abort_unless(auth()->user()->role === 'admin', 403, 'Only an administrator can change the request status.');

        $appointment->update(['status' => 'cancelled']);

        return redirect()->route('appointments.index')->with('success', 'Appointment cancelled.');
    }

    private function authorizeAppointment(Appointment $appointment): void
    {
        // Admins can access every appointment. Teachers must be assigned to the student on it,
        // and students must own it; this protects direct URLs as well as links in the interface.
        $user = auth()->user();
        $isAssignedTeacher = $user->role === 'teacher'
            && $appointment->student()->where('assigned_teacher_id', $user->id)->exists();
        $isStudentOwner = $user->role === 'student' && (int) $appointment->student_id === (int) $user->id;

        abort_unless($user->role === 'admin' || $isAssignedTeacher || $isStudentOwner, 403);
    }

    /**
     * Build future half-hour slots on weekdays during business hours for the next 30 days.
     * Pending and approved appointments reserve a slot; the appointment being edited is excluded.
     */
    private function availableSlots(?Appointment $except = null): array
    {
        $start = now()->startOfDay();
        $end = now()->addDays(30)->endOfDay();
        // Fetch existing bookings once, then compare candidate slots in memory.
        $booked = Appointment::query()
            ->whereIn('status', ['pending', 'approved'])
            ->whereNotNull('scheduled_at')
            ->when($except, fn ($query) => $query->where('id', '!=', $except->getKey()))
            ->whereBetween('scheduled_at', [$start, $end])
            ->pluck('scheduled_at')
            ->map(fn ($scheduledAt) => \Illuminate\Support\Carbon::parse($scheduledAt)->format('Y-m-d H:i'))
            ->all();

        $slots = [];
        // Start at 8:00 and step by 30 minutes, stopping before 17:00 (last slot is 16:30).
        for ($day = $start->copy(); $day <= $end; $day->addDay()) {
            if ($day->isWeekend()) {
                continue;
            }
            for ($minutes = 8 * 60; $minutes < 17 * 60; $minutes += 30) {
                $slot = $day->copy()->setTime(intdiv($minutes, 60), $minutes % 60);
                if ($slot->lte(now())) {
                    continue;
                }
                if (in_array($slot->format('Y-m-d H:i'), $booked, true)) {
                    continue;
                }
                $slots[] = [
                    'date' => $slot->format('Y-m-d'),
                    'date_label' => $slot->format('D, M j'),
                    'time' => $slot->format('H:i'),
                    'time_label' => $slot->format('g:i A'),
                ];
            }
        }

        return $slots;
    }

    private function isAvailableSlot(string $value, ?Appointment $appointment = null): bool
    {
        // Validate submitted dates independently of the date-picker list, since requests can be forged.
        $slot = \Illuminate\Support\Carbon::parse($value);
        if ($slot->lte(now()) || $slot->minute % 30 !== 0 || $slot->second !== 0 || $slot->isWeekend() || $slot->hour < 8 || $slot->hour >= 17) {
            return false;
        }

        return ! Appointment::query()
            ->when($appointment, fn ($query) => $query->where('id', '!=', $appointment->getKey()))
            ->whereIn('status', ['pending', 'approved'])
            ->whereNotNull('scheduled_at')
            ->whereDate('scheduled_at', $slot->toDateString())
            ->pluck('scheduled_at')
            ->contains(fn ($busy) => \Illuminate\Support\Carbon::parse($busy)->format('Y-m-d H:i') === $slot->format('Y-m-d H:i'));
    }

    private function selectedSchedule(Request $request, bool $required, ?Appointment $except = null): ?string
    {
        // The form posts date and time separately. Require both together, and allow both to be
        // blank only when the current role is allowed to leave the request unscheduled.
        $fields = $request->validate([
            'scheduled_date' => [Rule::requiredIf($required), 'nullable', 'date', 'after_or_equal:today'],
            'scheduled_time' => [Rule::requiredIf($required), 'nullable', 'date_format:H:i'],
        ]);

        $date = $fields['scheduled_date'] ?? null;
        $time = $fields['scheduled_time'] ?? null;
        if (! $date && ! $time) {
            return null;
        }
        if (! $date || ! $time) {
            // A half-filled schedule would leave an unusable date or time in the appointment.
            throw ValidationException::withMessages(['scheduled_date' => 'Choose both a date and a time, or leave both unassigned.']);
        }

        $datetime = "$date $time";
        // Keep the current appointment's existing slot when it is submitted unchanged.
        if ($except?->scheduled_at?->format('Y-m-d H:i') === $datetime) {
            return $datetime;
        }

        // Ensure the combined datetime is valid and still in the future before checking conflicts.
        Validator::make(['scheduled_at' => $datetime], [
            'scheduled_at' => ['required', 'date', 'after:now'],
        ])->validate();

        if (! $this->isAvailableSlot($datetime, $except)) {
            throw ValidationException::withMessages(['scheduled_time' => 'That time is unavailable. Choose another open slot.']);
        }

        return $datetime;
    }
}
