@extends('layouts.app')

@section('title', 'Edit case | GuidanceSched')
@section('page_title', 'Edit case record')
@section('page_description', $case->case_number . ' · ' . ($case->student?->name ?? 'Student unavailable'))

@section('content')
    <section class="card" style="max-width:900px">
        <div class="card-header">
            <div>
                <h2 class="card-title">Case information</h2>
                <p class="card-subtitle">Changes are visible to authorized guidance personnel.</p>
            </div>
            <a class="button button-secondary" href="{{ route('cases.show', $case) }}">Back to case</a>
        </div>
        @include('components.privacy-notice')
        <form method="POST" action="{{ route('cases.update', $case) }}" class="form-grid">
            @csrf @method('PUT')
            <div class="field field-full">
                <label for="title">Title</label>
                <input id="title" name="title" value="{{ old('title', $case->title) }}" maxlength="255" required>
            </div>
            <div class="field">
                <label for="category">Category</label>
                <select id="category" name="category" required>
                    @foreach (['academic', 'personal', 'social', 'career', 'other'] as $category)
                        <option value="{{ $category }}" @selected(old('category', $case->category) === $category)>{{ ucfirst($category) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field">
                <label for="status">Status</label>
                <select id="status" name="status" required>
                    @foreach (['open', 'in_progress', 'closed'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $case->status) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field field-full">
                <label for="description">Confidential description</label>
                <textarea id="description" name="description" maxlength="5000">{{ old('description', $case->description) }}</textarea>
            </div>
            <div class="field field-full">
                <label for="session_date">Session date</label>
                <input id="session_date" type="date" name="session_date" value="{{ old('session_date', $case->session_date) }}">
            </div>
            <div class="field">
                <label for="session_time">Session start time</label>
                <input id="session_time" type="time" name="session_time" value="{{ old('session_time', $case->session_time ? substr((string) $case->session_time, 0, 5) : null) }}">
            </div>
            <div class="field">
                <label for="session_end_time">Session end time</label>
                <input id="session_end_time" type="time" name="session_end_time" value="{{ old('session_end_time', $case->session_end_time ? substr((string) $case->session_end_time, 0, 5) : null) }}">
            </div>
            <div class="field field-full">
                <label for="remarks">Remarks</label>
                <textarea id="remarks" name="remarks" maxlength="5000">{{ old('remarks', $case->remarks) }}</textarea>
            </div>
            <div class="field field-full">
                <label for="findings">Findings</label>
                <textarea id="findings" name="findings" maxlength="5000">{{ old('findings', $case->findings) }}</textarea>
            </div>
            <div class="field field-full">
                <label for="recommendations">Recommendations</label>
                <textarea id="recommendations" name="recommendations" maxlength="5000">{{ old('recommendations', $case->recommendations) }}</textarea>
            </div>
            <div class="field field-full"><button class="button" type="submit">Save case changes</button></div>
        </form>
    </section>
@endsection
