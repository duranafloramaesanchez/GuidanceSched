@extends('layouts.app')

@section('title', 'Edit consultation | GuidanceSched')
@section('page_title', in_array(auth()->user()->role, ['admin', 'teacher'], true) ? 'Manage consultation' : 'Edit consultation request')
@section('page_description', in_array(auth()->user()->role, ['admin', 'teacher'], true) ? 'Assign an available time without overlapping another consultation.' : 'Update the topic or reason for your request.')

@section('content')
    <section class="card" style="max-width:850px">
        <div class="card-header"><div><h2 class="card-title">{{ $appointment->title }}</h2><p class="card-subtitle">Submitted {{ $appointment->created_at->format('M j, Y') }}</p></div><a class="button button-secondary" href="{{ route('appointments.index') }}">Back to consultations</a></div>
        <form id="appointment-update-form" method="POST" action="{{ route('appointments.update', $appointment) }}">
            @csrf @method('PUT')
            @include('components.privacy-notice')
            @if (in_array(auth()->user()->role, ['admin', 'teacher'], true))
                <div class="form-grid">
                    <div class="field"><label>Requester</label><div class="primary-text">{{ $appointment->student?->name ?? 'Unavailable' }}</div><div class="field-help">{{ $appointment->student?->email }}</div></div>
                    @if (auth()->user()->role === 'admin')<div class="field"><label>Created by</label><div class="primary-text">{{ $appointment->createdBy?->name ?? 'Not recorded' }}</div><div class="field-help">{{ $appointment->createdBy ? ($appointment->createdBy->role === 'admin' ? 'Administrator' : ucfirst($appointment->createdBy->role)) : '' }}</div></div>@endif
                    <div class="field field-full"><label>Internal reason</label><div>{{ $appointment->reason ?: 'No reason provided.' }}</div></div>
                    <div class="field field-full">
                        <div class="form-grid">
                            <div class="field">
                                <label for="scheduled_date">Consultation date</label>
                                <input id="scheduled_date" name="scheduled_date" type="date" value="{{ old('scheduled_date', $appointment->scheduled_at?->format('Y-m-d')) }}" min="{{ now()->format('Y-m-d') }}" max="{{ now()->addDays(30)->format('Y-m-d') }}">
                            </div>
                            <div class="field">
                                <label for="scheduled_time">Consultation time</label>
                                <select id="scheduled_time" name="scheduled_time" disabled>
                                    <option value="">Choose a time</option>
                                </select>
                            </div>
                        </div>
                        <div class="field-help">Only open weekday half-hour slots between 8:00 AM and 5:00 PM are listed.</div>
                    </div>
                    @if (auth()->user()->role === 'admin')
                        <div class="field"><label for="status">Request status</label><select id="status" name="status" required>@foreach (\App\Models\Appointment::STATUSES as $status)<option value="{{ $status }}" @selected(old('status', $appointment->status) === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>@endforeach</select></div>
                        <div class="field"><label for="assigned_to">Assigned guidance personnel</label><select id="assigned_to" name="assigned_to"><option value="">Unassigned</option>@foreach ($personnel as $person)<option value="{{ $person->id }}" @selected(old('assigned_to', $appointment->assigned_to) == $person->id)>{{ $person->name }}</option>@endforeach</select></div>
                        <div class="field field-full"><label for="notes">Private staff notes</label><textarea id="notes" name="notes" maxlength="2000" placeholder="Visible only to guidance administrators">{{ old('notes', $appointment->notes) }}</textarea></div>
                    @else
                        <div class="field field-full"><span class="field-help">Current status: <strong>{{ ucfirst($appointment->status) }}</strong>. Only administrators can change status.</span></div>
                    @endif
                </div>
            @else
                <div class="form-grid">
                    <div class="field field-full"><label for="title">Consultation topic</label><input id="title" name="title" value="{{ old('title', $appointment->title) }}" maxlength="255" required></div>
                    <div class="field field-full"><label for="reason">Reason <span class="field-help">(optional)</span></label><textarea id="reason" name="reason" maxlength="2000">{{ old('reason', $appointment->reason) }}</textarea></div>
                </div>
            @endif
        </form>
        <div class="actions" style="margin-top:20px; justify-content:space-between">
            @if (auth()->user()->role === 'admin')
                <form method="POST" action="{{ route('appointments.destroy', $appointment) }}" onsubmit="return confirm('Permanently delete this consultation request? This cannot be undone.')">
                    @csrf @method('DELETE')<button class="button button-danger" type="submit">Delete appointment</button>
                </form>
            @endif
            <div class="actions">
                <button class="button" type="submit" form="appointment-update-form">Save changes</button>
                <a class="button button-secondary" href="{{ route('appointments.index') }}">Cancel</a>
            </div>
        </div>
    </section>
    @push('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                window.setupAvailableSlotPicker(
                    document.getElementById('scheduled_date'),
                    document.getElementById('scheduled_time'),
                    @json(route('appointments.available-times', [], false)),
                    @json(old('scheduled_time', $appointment->scheduled_at?->format('H:i') ?? '')),
                    false,
                    @json($appointment->id)
                );
            });
        </script>
    @endpush
@endsection
