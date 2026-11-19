@extends('layouts.app')

@section('title', 'Add user | GuidanceSched')
@section('page_title', 'Add user account')
@section('page_description', 'Create a secure account for a member of the school community.')

@section('content')
    <div class="content-grid">
        <section class="card">
            <div class="card-header"><div><h2 class="card-title">New account</h2><p class="card-subtitle">Choose the role that matches the person’s access.</p></div></div>
            <form method="POST" action="{{ route('users.store') }}">
                @csrf
                <div class="form-grid">
                    <div class="field field-full"><label for="name">Full name</label><input id="name" name="name" type="text" value="{{ old('name') }}" required maxlength="255" autocomplete="name"></div>
                    <div class="field field-full"><label for="email">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email"></div>
                    <div class="field field-full"><label for="role">Account role</label><select id="role" name="role" required><option value="">Select a role</option><option value="student" @selected(old('role') === 'student')>Student</option><option value="teacher" @selected(old('role') === 'teacher')>Teacher</option><option value="admin" @selected(old('role') === 'admin')>Administrator / Guidance Personnel</option></select><div class="field-help">Administrators can manage guidance records and user accounts.</div></div>
                    <div class="field field-full" data-student-profile hidden>
                        <h3 class="card-title" style="margin:8px 0 14px">Student information</h3>
                        <div class="form-grid">
                            <div class="field field-full"><label for="assigned_teacher_id">Assigned teacher <span class="field-help" style="display:inline">(optional)</span></label><select id="assigned_teacher_id" name="assigned_teacher_id"><option value="">Not assigned yet</option>@foreach ($teachers as $teacher)<option value="{{ $teacher->id }}" @selected((string) old('assigned_teacher_id') === (string) $teacher->id)>{{ $teacher->name }}</option>@endforeach</select><div class="field-help">Teachers can only view students assigned to them.</div></div>
                            <div class="field"><label for="student_number">Student ID</label><input id="student_number" name="student_number" value="{{ old('student_number') }}" maxlength="50" data-student-required></div>
                            <div class="field"><label for="grade_level">Grade level</label><input id="grade_level" name="grade_level" value="{{ old('grade_level') }}" maxlength="50" placeholder="e.g. Grade 10" data-student-required></div>
                            <div class="field"><label for="section">Section</label><input id="section" name="section" value="{{ old('section') }}" maxlength="100" data-student-required></div>
                            <div class="field"><label for="contact_number">Contact number</label><input id="contact_number" name="contact_number" type="tel" value="{{ old('contact_number') }}" maxlength="50" autocomplete="tel" data-student-required></div>
                            <div class="field field-full"><label for="address">Address <span class="field-help" style="display:inline">(optional)</span></label><textarea id="address" name="address" maxlength="1000" autocomplete="street-address">{{ old('address') }}</textarea></div>
                            <div class="field"><label for="guardian_name">Guardian name</label><input id="guardian_name" name="guardian_name" value="{{ old('guardian_name') }}" maxlength="255" data-student-required></div>
                            <div class="field"><label for="guardian_relationship">Relationship to student</label><input id="guardian_relationship" name="guardian_relationship" value="{{ old('guardian_relationship') }}" maxlength="100" placeholder="e.g. Parent" data-student-required></div>
                            <div class="field field-full"><label for="guardian_contact_number">Guardian contact number</label><input id="guardian_contact_number" name="guardian_contact_number" type="tel" value="{{ old('guardian_contact_number') }}" maxlength="50" data-student-required></div>
                        </div>
                    </div>
                    <div class="field"><label for="password">Initial password</label><input id="password" name="password" type="password" required minlength="8" autocomplete="new-password"></div>
                    <div class="field"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required minlength="8" autocomplete="new-password"></div>
                </div>
                <div class="actions" style="margin-top:20px"><button class="button" type="submit">Create account</button><a class="button button-secondary" href="{{ route('users.index') }}">Cancel</a></div>
            </form>
        </section>
        <aside class="card"><h2 class="card-title">Account roles</h2><p class="card-subtitle">Access is based on the selected role.</p><dl class="detail-list" style="margin-top:18px"><div class="detail-item"><dt>Student</dt><dd>View assigned consultation schedule and status</dd></div><div class="detail-item"><dt>Teacher</dt><dd>Create and schedule student requests</dd></div><div class="detail-item"><dt>Administrator</dt><dd>Manage consultations, cases, and accounts</dd></div></dl></aside>
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
