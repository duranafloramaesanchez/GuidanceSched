@extends('layouts.app')

@section('title', $case->case_number . ' | GuidanceSched')
@section('page_title', $case->title)
@section('page_description', $case->case_number . ' · Confidential case record')

@section('content')
    <div class="content-stack">
        <section class="card">
            <div class="card-header">
                <div>
                    <span class="badge badge-{{ $case->status }}">{{ ucfirst(str_replace('_', ' ', $case->status)) }}</span>
                    <p class="card-subtitle" style="margin-top:9px">{{ ucfirst($case->category) }} · Created {{ $case->created_at->format('M d, Y') }}</p>
                </div>
                <div class="actions">
                    <a class="button button-secondary" href="{{ route('cases.index') }}">Back to cases</a>
                    <a class="button" href="{{ route('cases.edit', $case) }}">Edit case</a>
                </div>
            </div>
            <dl class="detail-list">
                <div class="detail-item"><dt>Student</dt><dd>{{ $case->student?->name ?? 'Unavailable' }}</dd></div>
                <div class="detail-item"><dt>Created by</dt><dd>{{ $case->creator?->name ?? 'Unavailable' }}</dd></div>
                @if ($case->appointment)
                    <div class="detail-item"><dt>Related consultation</dt><dd>{{ $case->appointment->title }} · {{ $case->appointment->scheduled_at->format('M d, Y · h:i A') }}</dd></div>
                @endif
            </dl>
            <div style="margin-top:22px;padding-top:18px;border-top:1px solid var(--line)">
                <h2 class="card-title">Confidential description</h2>
                <p class="note-text" style="margin-bottom:0;color:#526662;font-size:13px">{{ $case->description ?: 'No description provided.' }}</p>
            </div>
            <dl class="detail-list" style="margin-top:22px">
                <div class="detail-item"><dt>Session date</dt><dd>{{ $case->session_date ? \Illuminate\Support\Carbon::parse($case->session_date)->format('M d, Y') : 'Not recorded' }}</dd></div>
                <div class="detail-item"><dt>Session start time</dt><dd>{{ $case->session_time ? \Illuminate\Support\Carbon::parse($case->session_time)->format('h:i A') : 'Not recorded' }}</dd></div>
                <div class="detail-item"><dt>Session end time</dt><dd>{{ $case->session_end_time ? \Illuminate\Support\Carbon::parse($case->session_end_time)->format('h:i A') : 'Not recorded' }}</dd></div>
            </dl>
            @foreach (['remarks' => 'Remarks', 'findings' => 'Findings', 'recommendations' => 'Recommendations'] as $field => $label)
                <div style="margin-top:18px;padding-top:16px;border-top:1px solid var(--line)">
                    <h2 class="card-title">{{ $label }}</h2>
                    <p class="note-text" style="margin-bottom:0;color:#526662;font-size:13px;white-space:pre-line">{{ $case->{$field} ?: 'None recorded.' }}</p>
                </div>
            @endforeach
        </section>

    </div>
@endsection
