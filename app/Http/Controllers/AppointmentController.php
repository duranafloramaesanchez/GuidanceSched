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
    // Shows the appointment page. The query is limited to records the signed-in user may see.
    public function index(Request $request): View
    {
        $user = auth()->user();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(Appointment::STATUSES)],
        ]);

        $query = Appointment::with(['student', 'assignedPersonnel', 'createdBy'])->latest();

        // Students see their own requests; teachers see only requests for students assigned to them.
        if ($user->role === 'student') {
            $query->where('student_id', $user->id);
        } elseif ($user->role === 'teacher') {
            $query->whereHas('student', fn ($students) => $students->where('assigned_teacher_id', $user->id));
        }

        if ($user->role === 'student') {
            // Students only see their own requests after staff assigns a consultation time.
            $query->whereNotNull('scheduled_at');
        } else {
            if ($status = $filters['status'] ?? null) {
                $query->where('status', $status);
            }

            $search = trim((string) ($filters['search'] ?? ''));
        }

        if ($user->role !== 'student' && $search !== '') {
            $query->where(function ($builder) use ($search, $user) {
                $builder->where('title', 'like', "%{$search}%");
                if ($user->role !== 'student') {
                    $builder->orWhereHas('student', fn ($students) => $students->where('name', 'like', "%{$search}%"));
                }
            });
        }

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
        // Only staff may ask the date picker for free consultation times.
        abort_unless(in_array($request->user()->role, ['admin', 'teacher'], true), 403);

        // This endpoint is for the picker’s background request, not direct browser navigation.
        if (! $request->expectsJson()) {
            return redirect()->route('appointments.index');
        }

        $validated = $request->validate([
            'date' => ['required', 'date', 'after_or_equal:today', 'before_or_equal:' . now()->addDays(30)->toDateString()],
            'appointment_id' => ['nullable', 'integer', Rule::exists('appointments', 'id')],
        ]);

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

        // Only staff can create a request for a student.
        abort_unless(
            in_array($user->role, ['admin', 'teacher'], true),
            403,
            'Only teachers or administrators can create consultation requests.'
        );

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
        $scheduledAt = $this->selectedSchedule($request, $user->role === 'teacher');

        // New requests always begin as pending and use a 30-minute default length.
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
        $this->authorizeAppointment($appointment);
        $user = $request->user();

        // Administrators manage status and staff-only notes; teachers may only set a pending request's time.
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
            abort_unless($appointment->status === 'pending', 403, 'Teachers can assign times to pending requests only.');
            $scheduledAt = $this->selectedSchedule($request, false, $appointment);
            $appointment->update(['scheduled_at' => $scheduledAt]);
        } else {
            abort(403, 'Students can only view consultation dates and times assigned to them.');
        }

        return redirect()->route('appointments.index')->with('success', 'Appointment updated successfully.');
    }

    public function cancel(Appointment $appointment): RedirectResponse
    {
        $this->authorizeAppointment($appointment);
        abort_unless(auth()->user()->role === 'admin', 403, 'Only an administrator can change the request status.');

        $appointment->update(['status' => 'cancelled']);

        return redirect()->route('appointments.index')->with('success', 'Appointment cancelled.');
    }

    private function authorizeAppointment(Appointment $appointment): void
    {
        // An admin can access every appointment. A teacher needs to be assigned to the student.
        // A student can access only their own appointment.
        $user = auth()->user();
        $isAssignedTeacher = $user->role === 'teacher'
            && $appointment->student()->where('assigned_teacher_id', $user->id)->exists();
        $isStudentOwner = $user->role === 'student' && (int) $appointment->student_id === (int) $user->id;

        abort_unless($user->role === 'admin' || $isAssignedTeacher || $isStudentOwner, 403);
    }

    /** Build open half-hour weekday slots, from 8 AM to 5 PM, for the next 30 days. */
    private function availableSlots(?Appointment $except = null): array
    {
        $start = now()->startOfDay();
        $end = now()->addDays(30)->endOfDay();
        $booked = Appointment::query()
            ->whereIn('status', ['pending', 'approved'])
            ->whereNotNull('scheduled_at')
            ->when($except, fn ($query) => $query->where('id', '!=', $except->getKey()))
            ->whereBetween('scheduled_at', [$start, $end])
            ->pluck('scheduled_at')
            ->map(fn ($scheduledAt) => \Illuminate\Support\Carbon::parse($scheduledAt)->format('Y-m-d H:i'))
            ->all();

        $slots = [];
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
        // The form sends date and time separately; combine and validate them before saving.
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
            throw ValidationException::withMessages(['scheduled_date' => 'Choose both a date and a time, or leave both unassigned.']);
        }

        $datetime = "$date $time";
        if ($except?->scheduled_at?->format('Y-m-d H:i') === $datetime) {
            return $datetime;
        }

        Validator::make(['scheduled_at' => $datetime], [
            'scheduled_at' => ['required', 'date', 'after:now'],
        ])->validate();

        if (! $this->isAvailableSlot($datetime, $except)) {
            throw ValidationException::withMessages(['scheduled_time' => 'That time is unavailable. Choose another open slot.']);
        }

        return $datetime;
    }
}
