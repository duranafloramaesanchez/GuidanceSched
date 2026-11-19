@extends('layouts.app')

@section('title', 'Student details | GuidanceSched')
@section('page_title', 'Update student details')
@section('page_description', 'Update grade, section, contact information, and guardian details.')

@section('content')
    <section class="card" style="max-width:900px">
        <div class="card-header">
            <div><h2 class="card-title">{{ $user->name }}</h2><p class="card-subtitle">Student ID: {{ $user->student_number ?: 'Not provided' }} · {{ $user->email }}</p></div>
            <a class="button button-secondary" href="{{ route('students.index') }}">Back to students</a>
        </div>
        <form method="POST" action="{{ route('students.details.update', $user) }}">
            @csrf @method('PUT')
            <div class="form-grid">
                <div class="field"><label for="grade_level">Grade level</label><input id="grade_level" name="grade_level" value="{{ old('grade_level', $user->grade_level) }}" maxlength="50" placeholder="e.g. Grade 10"></div>
                <div class="field"><label for="section">Section</label><input id="section" name="section" value="{{ old('section', $user->section) }}" maxlength="100"></div>
                <div class="field"><label for="contact_number">Student contact number</label><input id="contact_number" name="contact_number" type="tel" value="{{ old('contact_number', $user->contact_number) }}" maxlength="50" autocomplete="tel"></div>
                <div class="field field-full"><label for="address">Address</label><textarea id="address" name="address" maxlength="1000" autocomplete="street-address">{{ old('address', $user->address) }}</textarea></div>
                <div class="field"><label for="guardian_name">Guardian name</label><input id="guardian_name" name="guardian_name" value="{{ old('guardian_name', $user->guardian_name) }}" maxlength="255"></div>
                <div class="field"><label for="guardian_relationship">Relationship to student</label><input id="guardian_relationship" name="guardian_relationship" value="{{ old('guardian_relationship', $user->guardian_relationship) }}" maxlength="100" placeholder="e.g. Parent"></div>
                <div class="field field-full"><label for="guardian_contact_number">Guardian contact number</label><input id="guardian_contact_number" name="guardian_contact_number" type="tel" value="{{ old('guardian_contact_number', $user->guardian_contact_number) }}" maxlength="50"></div>
            </div>
            <div class="actions" style="margin-top:20px"><button class="button" type="submit">Save student details</button><a class="button button-secondary" href="{{ route('students.index') }}">Cancel</a></div>
        </form>
    </section>
@endsection
