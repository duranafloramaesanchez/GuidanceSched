<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\CaseRecord;
use App\Models\FollowUp;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
    }

    private function ensureTeacherOrAdmin(): void
    {
        abort_unless(in_array(auth()->user()?->role, ['teacher', 'admin'], true), 403);
    }

    public function index(Request $request): View
    {
        $this->ensureAdmin();

        $users = User::query()
            ->select('id', 'name', 'email', 'role', 'created_at')
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim()->toString();
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->input('role')))
            ->orderBy('role')->orderBy('name')->paginate(15)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function students(Request $request): View
    {
        $this->ensureTeacherOrAdmin();

        $students = User::query()
            ->where('role', 'student')
            ->when(auth()->user()->role === 'teacher', fn ($query) => $query->where('assigned_teacher_id', auth()->id()))
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->string('search')->trim()->toString();
                $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")
                    ->orWhere('student_number', 'like', "%{$search}%"));
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.students', compact('students'));
    }

    public function editStudentDetails(User $user): View
    {
        $this->ensureTeacherOrAdmin();
        abort_unless($user->role === 'student', 404);
        $this->ensureStudentIsAssignedToCurrentTeacher($user);

        return view('users.student-details', compact('user'));
    }

    public function updateStudentDetails(Request $request, User $user): RedirectResponse
    {
        $this->ensureTeacherOrAdmin();
        abort_unless($user->role === 'student', 404);
        $this->ensureStudentIsAssignedToCurrentTeacher($user);

        $validated = $request->validate([
            'grade_level' => ['nullable', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'guardian_name' => ['nullable', 'string', 'max:255'],
            'guardian_relationship' => ['nullable', 'string', 'max:100'],
            'guardian_contact_number' => ['nullable', 'string', 'max:50'],
        ]);

        $user->update($validated);

        return redirect()->route('students.index')->with('success', 'Student details updated successfully.');
    }

    public function create(): View
    {
        $this->ensureAdmin();

        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']);

        return view('users.create', compact('teachers'));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in(['admin', 'teacher', 'student'])],
            'assigned_teacher_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'teacher'))],
            'student_number' => ['required_if:role,student', 'nullable', 'string', 'max:50', Rule::unique('users', 'student_number')],
            'grade_level' => ['required_if:role,student', 'nullable', 'string', 'max:50'],
            'section' => ['required_if:role,student', 'nullable', 'string', 'max:100'],
            'contact_number' => ['required_if:role,student', 'nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'guardian_name' => ['required_if:role,student', 'nullable', 'string', 'max:255'],
            'guardian_relationship' => ['required_if:role,student', 'nullable', 'string', 'max:100'],
            'guardian_contact_number' => ['required_if:role,student', 'nullable', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validated['role'] !== 'student') {
            $validated = array_merge($validated, $this->emptyStudentProfile());
        }

        User::create($validated);

        return redirect()->route('users.index')
            ->with('success', 'Account created successfully.');
    }

    public function edit(User $user): View
    {
        $this->ensureAdmin();

        $teachers = User::where('role', 'teacher')->orderBy('name')->get(['id', 'name']);

        return view('users.edit', compact('user', 'teachers'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'role' => ['required', Rule::in(['admin', 'teacher', 'student'])],
            'assigned_teacher_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where(fn ($query) => $query->where('role', 'teacher'))],
            'student_number' => ['required_if:role,student', 'nullable', 'string', 'max:50', Rule::unique('users', 'student_number')->ignore($user->id)],
            'grade_level' => ['required_if:role,student', 'nullable', 'string', 'max:50'],
            'section' => ['required_if:role,student', 'nullable', 'string', 'max:100'],
            'contact_number' => ['required_if:role,student', 'nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:1000'],
            'guardian_name' => ['required_if:role,student', 'nullable', 'string', 'max:255'],
            'guardian_relationship' => ['required_if:role,student', 'nullable', 'string', 'max:100'],
            'guardian_contact_number' => ['required_if:role,student', 'nullable', 'string', 'max:50'],
        ]);

        if ($validated['role'] !== 'student') {
            $validated = array_merge($validated, $this->emptyStudentProfile());
        }

        if (
            $user->id === $request->user()->id &&
            $validated['role'] !== 'admin'
        ) {
            return back()->withErrors([
                'role' => 'You cannot remove your own administrator role.',
            ])->withInput();
        }

        if (
            $user->role === 'admin' &&
            $validated['role'] !== 'admin' &&
            User::where('role', 'admin')->count() <= 1
        ) {
            return back()->withErrors([
                'role' => 'The system must retain at least one administrator.',
            ])->withInput();
        }

        $user->update($validated);

        return redirect()->route('users.index')
            ->with('success', 'Account updated successfully.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->password = Hash::make($validated['password']);
        $user->save();

        return redirect()->route('users.edit', $user)
            ->with('success', 'Password reset successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureAdmin();

        if ($user->id === auth()->id()) {
            return back()->withErrors([
                'delete' => 'You cannot delete your own account.',
            ]);
        }

        if (
            $user->role === 'admin' &&
            User::where('role', 'admin')->count() <= 1
        ) {
            return back()->withErrors([
                'delete' => 'The last administrator account cannot be deleted.',
            ]);
        }

        // Keep accounts with linked history so deleting them cannot cascade-delete records.
        if (
            $user->appointmentsAsStudent()->exists() ||
            $user->appointmentsAssigned()->exists() ||
            $user->createdCases()->exists() ||
            CaseRecord::where('student_id', $user->id)->exists() ||
            FollowUp::where('created_by', $user->id)->exists()
        ) {
            return back()->withErrors([
                'delete' => 'This account has related records. Preserve its history instead of deleting it.',
            ]);
        }

        $user->delete();

        return redirect()->route('users.index')
            ->with('success', 'Account deleted successfully.');
    }

    private function emptyStudentProfile(): array
    {
        return [
            'assigned_teacher_id' => null,
            'student_number' => null,
            'grade_level' => null,
            'section' => null,
            'contact_number' => null,
            'address' => null,
            'guardian_name' => null,
            'guardian_relationship' => null,
            'guardian_contact_number' => null,
        ];
    }

    private function ensureStudentIsAssignedToCurrentTeacher(User $student): void
    {
        if (auth()->user()->role === 'teacher') {
            abort_unless((int) $student->assigned_teacher_id === (int) auth()->id(), 404);
        }
    }
}
