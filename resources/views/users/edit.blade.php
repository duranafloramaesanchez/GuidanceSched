@extends('layouts.app')

@section('title', 'Edit user | GuidanceSched')
@section('page_title', 'Edit user account')
@section('page_description', 'Update account details and reset credentials when needed.')

@section('content')
    <div class="content-grid">
        <section class="card">
            <div class="card-header"><div><h2 class="card-title">Account details</h2><p class="card-subtitle">Update {{ $user->name }}’s profile and access role.</p></div></div>
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf @method('PUT')
                <div class="form-grid">
                    <div class="field field-full"><label for="name">Full name</label><input id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required maxlength="255" autocomplete="name"></div>
                    <div class="field field-full"><label for="email">Email address</label><input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required maxlength="255" autocomplete="email"></div>
                    <div class="field field-full"><label for="role">Account role</label><select id="role" name="role" required><option value="student" @selected(old('role', $user->role) === 'student')>Student</option><option value="teacher" @selected(old('role', $user->role) === 'teacher')>Teacher</option><option value="admin" @selected(old('role', $user->role) === 'admin')>Administrator / Guidance Personnel</option></select><div class="field-help">At least one administrator account must remain active.</div></div>
                    <div class="field field-full" data-student-profile hidden>
                        <h3 class="card-title" style="margin:8px 0 14px">Student information</h3>
                        <div class="form-grid">
                            <div class="field field-full"><label for="assigned_teacher_id">Assigned teacher <span class="field-help" style="display:inline">(optional)</span></label><select id="assigned_teacher_id" name="assigned_teacher_id"><option value="">Not assigned yet</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string) old('assigned_teacher_id', $user->assigned_teacher_id) === (string) $teacher->id)>{{ $teacher->name }}</option>@endforeach</select><div class="field-help">Teachers can only view students assigned to them.</div></div>
                            <div class="field"><label for="student_number">Student ID</label><input id="student_number" name="student_number" value="{{ old('student_number', $user->student_number) }}" maxlength="50" data-student-required></div>
                            <div class="field"><label for="grade_level">Grade level</label><input id="grade_level" name="grade_level" value="{{ old('grade_level', $user->grade_level) }}" maxlength="50" placeholder="e.g. Grade 10" data-student-required></div>
                            <div class="field"><label for="section">Section</label><input id="section" name="section" value="{{ old('section', $user->section) }}" maxlength="100" data-student-required></div>
                            <div class="field"><label for="contact_number">Contact number</label><input id="contact_number" name="contact_number" type="tel" value="{{ old('contact_number', $user->contact_number) }}" maxlength="50" autocomplete="tel" data-student-required></div>
                            <div class="field field-full"><label for="address">Address <span class="field-help" style="display:inline">(optional)</span></label><textarea id="address" name="address" maxlength="1000" autocomplete="street-address">{{ old('address', $user->address) }}</textarea></div>
                            <div class="field"><label for="guardian_name">Guardian name</label><input id="guardian_name" name="guardian_name" value="{{ old('guardian_name', $user->guardian_name) }}" maxlength="255" data-student-required></div>
                            <div class="field"><label for="guardian_relationship">Relationship to student</label><input id="guardian_relationship" name="guardian_relationship" value="{{ old('guardian_relationship', $user->guardian_relationship) }}" maxlength="100" placeholder="e.g. Parent" data-student-required></div>
                            <div class="field field-full"><label for="guardian_contact_number">Guardian contact number</label><input id="guardian_contact_number" name="guardian_contact_number" type="tel" value="{{ old('guardian_contact_number', $user->guardian_contact_number) }}" maxlength="50" data-student-required></div>
                        </div>
                    </div>
                </div>
                <div class="actions" style="margin-top:20px"><button class="button" type="submit">Save changes</button><a class="button button-secondary" href="{{ route('users.index') }}">Cancel</a></div>
            </form>
        </section>
        <section class="card">
            <div class="card-header"><div><h2 class="card-title">Reset password</h2><p class="card-subtitle">Set a new password for this account. Existing passwords cannot be viewed.</p></div></div>
            <form method="POST" action="{{ route('users.reset-password', $user) }}">
                @csrf @method('PATCH')
                <div class="form-grid">
                    <div class="field field-full"><label for="password">New password</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"></div>
                    <div class="field field-full"><label for="password_confirmation">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div>
                </div>
                <button class="button button-secondary" style="margin-top:18px" type="submit">Reset password</button>
            </form>
        </section>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const role = document.getElementById('role');
            const profile = document.querySelector('[data-student-profile]');
            const syncStudentFields = () => {
                const isStudent = role.value === 'student';
                profile.hidden = !isStudent;
                profile.querySelectorAll('[data-student-required]').forEach((field) => field.required = isStudent);
            };
            role.addEventListener('change', syncStudentFields);
            syncStudentFields();
        });
    </script>
@endpush
