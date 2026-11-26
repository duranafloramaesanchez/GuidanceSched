@extends('layouts.app')

@section('title', 'Create case | GuidanceSched')
@section('page_title', 'Create a case record')
@section('page_description', 'Add a confidential case reference for authorized guidance follow-up.')

@section('content')
    <section class="card" style="max-width:900px">
        <div class="card-header">
            <div>
                <h2 class="card-title">Case information</h2>
                <p class="card-subtitle">Enter information relevant to the guidance follow-up.</p>
            </div>
            <a class="button button-secondary" href="{{ route('cases.index') }}">Back to cases</a>
        </div>
        @include('components.privacy-notice')
        <form method="POST" action="{{ route('cases.store') }}" class="form-grid">
            @csrf
            <div class="field">
                <label for="student_id">Student</label>
                <select id="student_id" name="student_id" required>
                    <option value="">Select a student</option>
                    @foreach ($students as $student)
                        <option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }} · {{ $student->email }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="appointment_id">Related consultation <span class="field-help" style="display:inline">(optional)</span></label>
                <select id="appointment_id" name="appointment_id">
                    <option value="">No linked consultation</option>
                    @foreach ($appointments as $appointment)
                        <option value="{{ $appointment->id }}" @selected(old('appointment_id') == $appointment->id)>{{ $appointment->title }} · {{ $appointment->student?->name ?? 'Unknown student' }}</option>
                    @endforeach
                </select>
                <div class="field-help">The linked request must belong to the selected student.</div>
            </div>
            <div class="field">
                <label for="title">Case title</label>
                <input id="title" name="title" value="{{ old('title') }}" maxlength="255" required>
            </div>
            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    @foreach (['academic' => 'Academic', 'personal' => 'Personal', 'social' => 'Social', 'career' => 'Career', 'other' => 'Other'] as $value => $label)
                        <option value="{{ $value }}" @selected(old('category', 'other') === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    <option value="open" @selected(old('status', 'open') === 'open')>Open</option>
                    <option value="in_progress" @selected(old('status') === 'in_progress')>In progress</option>
                    <option value="closed" @selected(old('status') === 'closed')>Closed</option>
                </select>
            </div>
            <div class="field field-full">
                <label for="session_date">Session date <span class="field-help" style="display:inline">(optional)</span></label>
                <input id="session_date" type="date" name="session_date" value="{{ old('session_date') }}">
            </div>
            <div class="field">
                <label for="session_time">Session start time <span class="field-help" style="display:inline">(optional)</span></label>
                <input id="session_time" type="time" name="session_time" value="{{ old('session_time') }}">
            </div>
             <div class="field">
                <label for="session_end_time">Session end time <span class="field-help" style="display:inline">(optional)</span></label>
                <input id="session_end_time" type="time" name="session_end_time" value="{{ old('session_end_time') }}">
            </div>
            <div class="field field-full">
                <label for="description">Confidential description <span class="field-help" style="display:inline">(optional)</span></label>
                <textarea id="description" name="description" maxlength="5000" placeholder="Private guidance notes. Visible only to authorized staff.">{{ old('description') }}</textarea>
            </div>

            <div class="field field-full">
                <label for="remarks">Remarks <span class="field-help" style="display:inline">(optional)</span></label>
                <textarea id="remarks" name="remarks" maxlength="5000">{{ old('remarks') }}</textarea>
            </div>
            <div class="field field-full">
                <label for="findings">Findings <span class="field-help" style="display:inline">(optional)</span></label>
                <textarea id="findings" name="findings" maxlength="5000">{{ old('findings') }}</textarea>
            </div>
            <div class="field field-full">
                <label for="recommendations">Recommendations <span class="field-help" style="display:inline">(optional)</span></label>
                <textarea id="recommendations" name="recommendations" maxlength="5000">{{ old('recommendations') }}</textarea>
            </div>
            <div class="field field-full"><button class="button" type="submit">Create case record</button></div>
        </form>
    </section>
@endsection
