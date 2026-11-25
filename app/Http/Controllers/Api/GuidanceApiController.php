<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\CaseRecord;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GuidanceApiController extends Controller
{
    // Returns appointment data as JSON, filtered by the signed-in API user's role.
    public function appointments(Request $request): JsonResponse
    {
        // Validate filters before building the query so unsupported status values are rejected.
        $filters = $request->validate([
            'status' => ['nullable', Rule::in(Appointment::STATUSES)],
        ]);
        $query = Appointment::query()->latest();
        // Scope rows at the database level: students see their scheduled appointments only,
        // teachers see their assigned students' appointments, and admins see all appointments.
        if ($request->user()->role === 'student') {
            $query->where('student_id', $request->user()->id)
                ->whereNotNull('scheduled_at');
        } elseif ($request->user()->role === 'teacher') {
            $query->whereHas('student', fn ($students) => $students->where('assigned_teacher_id', $request->user()->id));
        }
        if ($request->user()->role !== 'student' && ($status = $filters['status'] ?? null)) {
            $query->where('status', $status);
        }

        // Keep the student response minimal; staff receive the broader appointment representation.
        return response()->json($query->paginate(20)->through(function (Appointment $appointment) use ($request) {
            return $request->user()->role === 'student'
                ? [
                    'title' => $appointment->title,
                    'scheduled_at' => $appointment->scheduled_at,
                    'status' => $appointment->status,
                ]
                : $this->appointmentData($appointment);
        }));
    }

    public function createAppointment(Request $request): JsonResponse
    {
        // Only staff can create requests. Teachers must select a student assigned to them and a time;
        // an admin may create an unassigned request for any student.
        $user = $request->user();
        abort_unless(in_array($user->role, ['admin', 'teacher'], true), 403);
        $data = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('users', 'id')->where(function ($query) use ($user) {
                $query->where('role', 'student');
                if ($user->role === 'teacher') {
                    $query->where('assigned_teacher_id', $user->id);
                }
            })],
            'title' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'scheduled_at' => [Rule::requiredIf($user->role === 'teacher'), 'nullable', 'date', 'after:now'],
        ]);
        // A valid date is not enough: enforce the same business-hour and conflict rules as the web form.
        if (! empty($data['scheduled_at']) && ! $this->isAvailableSlot($data['scheduled_at'])) {
            return response()->json(['message' => 'That time is unavailable. Choose another slot.'], 422);
        }

        $appointment = Appointment::create([
            ...$data,
            'student_id' => $data['student_id'],
            'created_by' => $user->id,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'duration_minutes' => 30,
            'status' => 'pending',
        ]);

        return response()->json(['data' => $this->appointmentData($appointment)], 201);
    }

    public function showAppointment(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeAppointment($request, $appointment);

        if ($request->user()->role === 'student') {
            // Do not expose an unassigned request or staff-only fields to a student.
            abort_unless($appointment->scheduled_at, 404);
            return response()->json(['data' => [
                'title' => $appointment->title,
                'scheduled_at' => $appointment->scheduled_at,
                'status' => $appointment->status,
            ]]);
        }

        return response()->json(['data' => $this->appointmentData($appointment)]);
    }

    public function updateAppointment(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeAppointment($request, $appointment);
        $user = $request->user();
        // Students may read limited schedule details, but only staff may change appointment records through the API.
        abort_unless($user->role !== 'student', 403, 'Students can only view assigned consultation dates, times, and status.');

        // Whitelist update fields by role so callers cannot change fields outside their responsibility.
        if ($user->role === 'admin') {
            $data = $request->validate([
                'status' => ['sometimes', 'required', Rule::in(Appointment::STATUSES)],
                'scheduled_at' => ['sometimes', 'nullable', 'date', 'after:now'],
                'assigned_to' => ['sometimes', 'nullable', 'integer', Rule::exists('users', 'id')->where('role', 'admin')],
                'notes' => ['sometimes', 'nullable', 'string', 'max:2000'],
            ]);
            abort_if($data === [], 422, 'Provide at least one field to update.');
            if (! empty($data['scheduled_at']) && ! $this->isAvailableSlot($data['scheduled_at'], $appointment)) {
                return response()->json(['message' => 'That time is unavailable. Choose another slot.'], 422);
            }
            // Approval without an assigned time would create an unusable appointment.
            if (($data['status'] ?? null) === 'approved' && empty($data['scheduled_at'] ?? $appointment->scheduled_at)) {
                return response()->json(['message' => 'Assign an available consultation time before approving.'], 422);
            }
        } elseif ($user->role === 'teacher') {
            // Teachers may assign a time only while the request is pending; status remains admin-controlled.
            abort_unless($appointment->status === 'pending', 403, 'Teachers can assign times to pending requests only.');
            $data = $request->validate([
                'scheduled_at' => ['sometimes', 'nullable', 'date', 'after:now'],
            ]);
            abort_if($data === [], 422, 'Provide an available scheduled_at time.');
            if (! empty($data['scheduled_at']) && ! $this->isAvailableSlot($data['scheduled_at'], $appointment)) {
                return response()->json(['message' => 'That time is unavailable. Choose another slot.'], 422);
            }
        } else {
            abort_unless($appointment->status === 'pending', 403);
            $data = $request->validate([
                'title' => ['sometimes', 'required', 'string', 'max:255'],
                'reason' => ['sometimes', 'nullable', 'string', 'max:2000'],
            ]);
            abort_if($data === [], 422, 'Provide at least one field to update.');
        }

        $appointment->update($data);

        return response()->json(['data' => $this->appointmentData($appointment->refresh())]);
    }

    public function cancelAppointment(Request $request, Appointment $appointment): JsonResponse
    {
        $this->authorizeAppointment($request, $appointment);
        abort_unless($request->user()->role === 'admin', 403, 'Only an administrator can change the request status.');
        $appointment->update(['status' => 'cancelled']);

        return response()->json(['data' => $this->appointmentData($appointment->refresh())]);
    }

    public function cases(Request $request): JsonResponse
    {
        // Case details and follow-up notes are confidential and are available to administrators only.
        $this->ensureAdmin($request);
        $filters = $request->validate([
            'status' => ['nullable', 'in:open,in_progress,closed'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);
        $query = CaseRecord::with('student')->latest();
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($search = $filters['search'] ?? null) {
            $query->where(fn ($builder) => $builder->where('case_number', 'like', "%{$search}%")
                ->orWhere('title', 'like', "%{$search}%")
                ->orWhereHas('student', fn ($students) => $students->where('name', 'like', "%{$search}%")));
        }

        return response()->json($query->paginate(20)->through(fn (CaseRecord $case) => $this->caseData($case)));
    }

    public function showCase(Request $request, CaseRecord $case): JsonResponse
    {
        $this->ensureAdmin($request);
        // Load the relationships used by the detailed response in one pass.
        $case->load(['student', 'followUps.creator']);

        return response()->json(['data' => $this->caseData($case, true)]);
    }

    public function updateCaseStatus(Request $request, CaseRecord $case): JsonResponse
    {
        $this->ensureAdmin($request);
        $data = $request->validate(['status' => ['required', 'in:open,in_progress,closed']]);
        $case->update($data);
        $case->load('student');

        return response()->json(['data' => $this->caseData($case->refresh())]);
    }

    public function followUps(Request $request, CaseRecord $case): JsonResponse
    {
        $this->ensureAdmin($request);

        // The case relationship scopes entries to this case; return creator identity without extra user fields.
        return response()->json(['data' => $case->followUps()->with('creator:id,name')->get()]);
    }

    public function createFollowUp(Request $request, CaseRecord $case): JsonResponse
    {
        $this->ensureAdmin($request);
        // Validate the note, then create it through its parent case so case_record_id cannot be spoofed.
        $data = $request->validate([
            'follow_up_date' => ['required', 'date'],
            'notes' => ['required', 'string', 'max:5000'],
            'action_taken' => ['nullable', 'string', 'max:255'],
        ]);
        $followUp = $case->followUps()->create([...$data, 'created_by' => $request->user()->id]);

        return response()->json(['data' => $followUp->load('creator:id,name')], 201);
    }

    private function appointmentData(Appointment $appointment): array
    {
        // This is the staff JSON format. Internal appointment notes are deliberately omitted.
        return [
            'id' => $appointment->id,
            'student_id' => $appointment->student_id,
            'title' => $appointment->title,
            'reason' => $appointment->reason,
            'scheduled_at' => $appointment->scheduled_at,
            'duration_minutes' => $appointment->duration_minutes,
            'status' => $appointment->status,
            'created_at' => $appointment->created_at,
        ];
    }

    private function isAvailableSlot(string $value, ?Appointment $appointment = null): bool
    {
        // Treat only future half-hour weekday slots between 08:00 and 17:00 as valid.
        $slot = \Illuminate\Support\Carbon::parse($value);
        if ($slot->minute % 30 !== 0 || $slot->second !== 0 || $slot->isWeekend() || $slot->hour < 8 || $slot->hour >= 17) {
            return false;
        }

        // Pending and approved bookings reserve their date/time; completed and cancelled ones do not.
        return ! Appointment::query()
            ->when($appointment, fn ($query) => $query->where('id', '!=', $appointment->getKey()))
            ->whereIn('status', ['pending', 'approved'])
            ->whereNotNull('scheduled_at')
            ->whereDate('scheduled_at', $slot->toDateString())
            ->pluck('scheduled_at')
            ->contains(fn ($busy) => \Illuminate\Support\Carbon::parse($busy)->format('Y-m-d H:i') === $slot->format('Y-m-d H:i'));
    }

    private function caseData(CaseRecord $case, bool $includeFollowUps = false): array
    {
        // Build one consistent JSON format for case lists and case details.
        $data = [
            'id' => $case->id,
            'case_number' => $case->case_number,
            'student' => $case->student?->only(['id', 'name']),
            'appointment_id' => $case->appointment_id,
            'title' => $case->title,
            'category' => $case->category,
            'description' => $case->description,
            'status' => $case->status,
            'created_at' => $case->created_at,
        ];

        if ($includeFollowUps) {
            // Include private follow-up content only in the admin-only detailed case response, not list rows.
            $data['follow_ups'] = $case->followUps->map(fn ($followUp) => [
                'id' => $followUp->id,
                'date' => $followUp->follow_up_date,
                'notes' => $followUp->notes,
                'action_taken' => $followUp->action_taken,
                'created_by' => $followUp->creator?->only(['id', 'name']),
            ]);
        }

        return $data;
    }

    private function authorizeAppointment(Request $request, Appointment $appointment): void
    {
        // Basic authentication identifies the caller; this ownership check decides which record they may access.
        $user = $request->user();
        $isAssignedTeacher = $user->role === 'teacher'
            && $appointment->student()->where('assigned_teacher_id', $user->id)->exists();
        $isStudentOwner = $user->role === 'student' && (int) $appointment->student_id === (int) $user->id;

        abort_unless($user->role === 'admin' || $isAssignedTeacher || $isStudentOwner, 403);
    }

    private function ensureAdmin(Request $request): void
    {
        abort_unless($request->user()->role === 'admin', 403);
    }
}
