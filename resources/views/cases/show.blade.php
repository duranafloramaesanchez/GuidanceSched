@extends('layouts.app')

{{-- Confidential case detail page. The controller allows administrator accounts only. --}}

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

        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">Follow-up history</h2>
                    <p class="card-subtitle">Private notes recorded for this case.</p>
                </div>
                <span class="badge badge-approved">{{ $case->followUps->count() }} entries</span>
            </div>

            @forelse ($case->followUps as $followUp)
                <article class="follow-up-item">
                    <div class="follow-up-date">
                        {{ $followUp->follow_up_date->format('M d, Y') }}
                        <span>· {{ $followUp->creator?->name ?? 'Guidance staff' }}</span>
                    </div>
                    @if ($followUp->action_taken)
                        <p class="card-subtitle" style="margin:7px 0">{{ $followUp->action_taken }}</p>
                    @endif
                    <p class="note-text" style="margin:7px 0 0;color:#526662;font-size:13px">{{ $followUp->notes }}</p>
                    <details class="follow-up-edit" @if ((string) old('_follow_up_id') === (string) $followUp->id) open @endif>
                        <summary class="button button-secondary button-small">Edit follow-up</summary>
                        <form method="POST" action="{{ route('cases.follow-ups.update', [$case, $followUp]) }}" class="form-grid follow-up-edit-form">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="_follow_up_id" value="{{ $followUp->id }}">
                            <div class="field">
                                <label for="follow_up_date_{{ $followUp->id }}">Follow-up date</label>
                                <input id="follow_up_date_{{ $followUp->id }}" type="date" name="follow_up_date" value="{{ (string) old('_follow_up_id') === (string) $followUp->id ? old('follow_up_date') : $followUp->follow_up_date->toDateString() }}" required>
                            </div>
                            <div class="field">
                                <label for="action_taken_{{ $followUp->id }}">Action taken</label>
                                <input id="action_taken_{{ $followUp->id }}" name="action_taken" value="{{ (string) old('_follow_up_id') === (string) $followUp->id ? old('action_taken') : $followUp->action_taken }}" maxlength="255">
                            </div>
                            <div class="field field-full">
                                <label for="notes_{{ $followUp->id }}">Notes</label>
                                <textarea id="notes_{{ $followUp->id }}" name="notes" maxlength="5000" required>{{ (string) old('_follow_up_id') === (string) $followUp->id ? old('notes') : $followUp->notes }}</textarea>
                            </div>
                            <div class="field field-full">
                                <button class="button" type="submit">Save changes</button>
                            </div>
                        </form>
                    </details>
                </article>
            @empty
                <div class="empty-state">No follow-ups have been recorded for this case.</div>
            @endforelse

            <div style="margin-top:18px;padding-top:18px;border-top:1px solid var(--line)">
                <h3 class="card-title">Add a follow-up</h3>
                <form method="POST" action="{{ route('cases.follow-ups.store', $case) }}" class="form-grid">
                    @csrf
                    <div class="field">
                        <label for="follow_up_date">Follow-up date</label>
                        <input id="follow_up_date" type="date" name="follow_up_date" value="{{ old('follow_up_date', today()->toDateString()) }}" required>
                    </div>
                    <div class="field">
                        <label for="action_taken">Action taken <span class="muted">(optional)</span></label>
                        <input id="action_taken" name="action_taken" value="{{ old('action_taken') }}" maxlength="255">
                    </div>
                    <div class="field field-full">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" maxlength="5000" required>{{ old('notes') }}</textarea>
                    </div>
                    <div class="field field-full">
                        <button class="button" type="submit">Save follow-up</button>
                    </div>
                </form>
            </div>
        </section>

    </div>
@endsection
