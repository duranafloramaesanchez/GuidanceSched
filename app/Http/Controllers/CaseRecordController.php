<?php

namespace App\Http\Controllers;

use App\Models\Appointment;
use App\Models\CaseRecord;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CaseRecordController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    public function index(Request $request): View
    {
        $this->ensureAdmin();
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'in:open,in_progress,closed'],
            'category' => ['nullable', 'in:academic,personal,social,career,other'],
        ]);

        $query = CaseRecord::with(['student', 'creator', 'appointment'])->latest();
        if ($status = $filters['status'] ?? null) {
            $query->where('status', $status);
        }
        if ($category = $filters['category'] ?? null) {
            $query->where('category', $category);
        }
        if ($search = trim((string) ($filters['search'] ?? ''))) {
            $query->where(function ($builder) use ($search) {
                $builder->where('case_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%")
                    ->orWhereHas('student', fn ($students) => $students->where('name', 'like', "%{$search}%"));
            });
        }
        $cases = $query->paginate(15)->withQueryString();

        return view('cases.index', compact('cases'));
    }

    public function create(): View
    {
        $this->ensureAdmin();

        $students = User::where('role', 'student')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        $appointments = Appointment::with('student')
            ->whereIn('status', ['pending', 'approved'])
            ->latest()
            ->get();

        return view('cases.create', compact('students', 'appointments'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'student_id' => ['required', 'integer', 'exists:users,id'],
            'appointment_id' => [
                'nullable',
                'integer',
                // A linked appointment must belong to this student and still be active.
                Rule::exists('appointments', 'id')->where(function ($query) use ($request) {
                    $query->where('student_id', $request->input('student_id'))
                        ->whereIn('status', ['pending', 'approved']);
                }),
            ],
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:academic,personal,social,career,other'],
            'description' => ['nullable', 'string', 'max:5000'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'session_date' => ['nullable', 'date'],
            'session_time' => ['nullable', 'date_format:H:i'],
            'session_end_time' => ['nullable', 'date_format:H:i'],
            'findings' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:open,in_progress,closed'],
        ]);

        // The ID only needs to point to a user; enforce the student role separately.
        abort_unless(
            User::whereKey($validated['student_id'])
                ->where('role', 'student')
                ->exists(),
            422,
            'Select a valid student account.'
        );

        $case = CaseRecord::create([
            'student_id' => $validated['student_id'],
            'created_by' => $request->user()->id,
            'appointment_id' => $validated['appointment_id'] ?? null,
            'case_number' => 'CASE-' . now()->format('Ymd') . '-' . Str::upper(Str::random(6)),
            'title' => $validated['title'],
            'category' => $validated['category'],
            'description' => $validated['description'] ?? null,
            'remarks' => $validated['remarks'] ?? null,
            'session_date' => $validated['session_date'] ?? null,
            'session_time' => $validated['session_time'] ?? null,
            'session_end_time' => $validated['session_end_time'] ?? null,
            'findings' => $validated['findings'] ?? null,
            'recommendations' => $validated['recommendations'] ?? null,
            'status' => $validated['status'],
        ]);

        return redirect()
            ->route('cases.show', $case)
            ->with('success', 'Case record created successfully.');
    }

    public function show(CaseRecord $case): View
    {
        $this->ensureAdmin();

        $case->load([
            'student',
            'creator',
            'appointment',
        ]);

        return view('cases.show', compact('case'));
    }

    public function edit(CaseRecord $case): View
    {
        $this->ensureAdmin();
        $case->load('student');

        return view('cases.edit', compact('case'));
    }

    public function update(Request $request, CaseRecord $case): RedirectResponse
    {
        $this->ensureAdmin();
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:academic,personal,social,career,other'],
            'description' => ['nullable', 'string', 'max:5000'],
            'remarks' => ['nullable', 'string', 'max:5000'],
            'session_date' => ['nullable', 'date'],
            'session_time' => ['nullable', 'date_format:H:i'],
            'session_end_time' => ['nullable', 'date_format:H:i'],
            'findings' => ['nullable', 'string', 'max:5000'],
            'recommendations' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', 'in:open,in_progress,closed'],
        ]);
        $case->update($validated);

        return redirect()->route('cases.show', $case)->with('success', 'Case record updated successfully.');
    }
}
