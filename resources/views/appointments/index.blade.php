@extends('layouts.app')

{{-- Appointment page: staff create requests; students see their assigned consultation times. --}}

@section('title', 'Consultations | GuidanceSched')
@section('page_title', auth()->user()->role === 'student' ? 'My consultation times' : (auth()->user()->role === 'admin' ? 'Consultations' : 'My consultations'))
@section('page_description', auth()->user()->role === 'admin' ? 'Create requests for students, assign available times, and manage consultation status.' : (auth()->user()->role === 'teacher' ? 'Create student requests and coordinate available consultation times.' : 'View only consultation dates and times assigned to you.'))

@section('content')
    <div class="content-stack">
        {{-- Only staff see the request form; the controller separately verifies role and student assignment. --}}
        @if (in_array(auth()->user()->role, ['admin', 'teacher'], true))
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Create a student consultation request</h2>
                        <p class="card-subtitle">Create the request and choose an available time for the selected student.</p>
                    </div>
                    <span class="badge">New request</span>
                </div>
                @include('components.privacy-notice')
                <form method="POST" action="{{ route('appointments.store') }}" class="form-grid">
                    @csrf
                    <div class="field field-full">
                        <label for="student_id">Student</label>
                        <select id="student_id" name="student_id" required>
                            <option value="">Select a student</option>
                            @foreach ($students as $student)
                                <option value="{{ $student->id }}" @selected((string) old('student_id') === (string) $student->id)>{{ $student->name }} · {{ $student->email }}</option>
                            @endforeach
                        </select>
                        @if (auth()->user()->role === 'teacher' && $students->isEmpty())
                            <div class="field-help">No students are assigned to your account yet. Ask an administrator to assign students first.</div>
                        @endif
                    </div>
                    <div class="field field-full">
                        <label for="title">Consultation topic</label>
                        <input id="title" name="title" value="{{ old('title') }}" maxlength="255" required placeholder="What would you like to discuss?">
                    </div>
                    <div class="field field-full">
                        <div class="form-grid">
                            <div class="field">
                                <label for="scheduled_date">Consultation date{{ auth()->user()->role === 'teacher' ? '' : ' (optional)' }}</label>
                                <input id="scheduled_date" name="scheduled_date" type="date" value="{{ old('scheduled_date') }}" min="{{ now()->format('Y-m-d') }}" max="{{ now()->addDays(30)->format('Y-m-d') }}" {{ auth()->user()->role === 'teacher' ? 'required' : '' }}>
                            </div>
                            <div class="field">
                                <label for="scheduled_time">Consultation time{{ auth()->user()->role === 'teacher' ? '' : ' (optional)' }}</label>
                                <select id="scheduled_time" name="scheduled_time" {{ auth()->user()->role === 'teacher' ? 'required' : '' }} disabled>
                                    <option value="">Choose a time</option>
                                </select>
                            </div>
                        </div>
                        <div class="field-help">Choose from open weekday half-hour slots between 8:00 AM and 5:00 PM.</div>
                    </div>
                    <div class="field field-full">
                        <label for="reason">Internal reason <span class="field-help" style="display:inline">(optional)</span></label>
                        <textarea id="reason" name="reason" maxlength="2000" placeholder="Staff reference only.">{{ old('reason') }}</textarea>
                    </div>
                    <div class="field" style="display:flex;align-items:end">
                        <button class="button" type="submit">Submit request <span aria-hidden="true">→</span></button>
                    </div>
                </form>
            </section>
            @push('scripts')
                <script>
                    document.addEventListener('DOMContentLoaded', () => {
                        window.setupAvailableSlotPicker(
                            document.getElementById('scheduled_date'),
                            document.getElementById('scheduled_time'),
                            @json(route('appointments.available-times', [], false)),
                            @json(old('scheduled_time', '')),
                            {{ auth()->user()->role === 'teacher' ? 'true' : 'false' }}
                        );
                    });
                </script>
            @endpush
        @else
            <section class="card" style="padding:14px 18px">
                <p class="card-subtitle" style="margin:0">Teachers and administrators create requests and assign consultation times. This page shows only the dates and times assigned to you.</p>
            </section>
        @endif

        <section class="card">
            <div class="card-header">
                <div>
                    <h2 class="card-title">{{ auth()->user()->role === 'student' ? 'Assigned consultation times' : 'All requests' }}</h2>
                    @if (auth()->user()->role !== 'student')<p class="card-subtitle">{{ $appointments->total() }} {{ $appointments->total() === 1 ? 'request' : 'requests' }}</p>@endif
                </div>
            </div>
            {{-- Search and status filters are staff-only; student queries are scoped to their own schedule. --}}
            @if (auth()->user()->role !== 'student')
                <form class="filter-bar" method="GET">
                    <input type="search" name="search" value="{{ request('search') }}" placeholder="Search topic or requester" aria-label="Search consultation requests">
                    <select name="status" aria-label="Filter by status"><option value="">All statuses</option>@foreach (\App\Models\Appointment::STATUSES as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>
                    <button class="button" type="submit">Apply filters</button>
                    @if (request()->hasAny(['search', 'status']))<a class="button button-secondary" href="{{ route('appointments.index') }}">Clear</a>@endif
                </form>
            @endif

            @if ($appointments->count())
                <div class="table-scroll">
                    <table>
                        <thead>
                            <tr>
                                @if (auth()->user()->role === 'student')
                                    <th>Consultation</th><th>Date and time</th><th>Status</th>
                                @else
                                    <th>Consultation</th>
                                    <th>Requester</th>
                                    @if (auth()->user()->role === 'admin')<th>Created by</th>@endif
                                    <th>Consultation time</th>
                                    <th>Status</th>
                                    @if (auth()->user()->role === 'admin')<th>Assigned to</th>@endif
                                    <th>Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($appointments as $appointment)
                                <tr>
                                    @if (auth()->user()->role === 'student')
                                        <td><span class="primary-text">{{ $appointment->title }}</span></td>
                                        <td>{{ $appointment->scheduled_at->format('l, F j, Y') }}<span class="secondary-text">{{ $appointment->scheduled_at->format('h:i A') }}</span></td>
                                        <td><span class="badge badge-{{ $appointment->status }}">{{ ucfirst(str_replace('_', ' ', $appointment->status)) }}</span></td>
                                    @else
                                        <td><span class="primary-text">{{ $appointment->title }}</span><span class="secondary-text">Request #{{ $appointment->id }}</span></td>
                                        <td>{{ $appointment->student?->name ?? 'Unavailable' }}</td>
                                        @if (auth()->user()->role === 'admin')
                                            <td>
                                                @if ($appointment->createdBy)
                                                    <span class="primary-text">{{ $appointment->createdBy->name }}</span>
                                                    <span class="secondary-text">{{ $appointment->createdBy->role === 'admin' ? 'Administrator' : ucfirst($appointment->createdBy->role) }}</span>
                                                @else
                                                    <span class="secondary-text">Not recorded</span>
                                                @endif
                                            </td>
                                        @endif
                                        <td>@if ($appointment->scheduled_at){{ $appointment->scheduled_at->format('M d, Y') }}<span class="secondary-text">{{ $appointment->scheduled_at->format('h:i A') }}</span>@else<span class="secondary-text">No time assigned</span>@endif</td>
                                        <td><span class="badge badge-{{ $appointment->status }}">{{ ucfirst($appointment->status) }}</span></td>
                                        @if (auth()->user()->role === 'admin')<td>{{ $appointment->assignedPersonnel?->name ?? 'Unassigned' }}</td>@endif
                                        <td><div class="actions">
                                            <a class="button button-secondary button-small" href="{{ route('appointments.edit', $appointment) }}">Manage</a>
                                            @if (auth()->user()->role === 'admin' && in_array($appointment->status, ['pending', 'approved'], true))
                                                <form class="inline-form" method="POST" action="{{ route('appointments.cancel', $appointment) }}" onsubmit="return confirm('Cancel this consultation request?')">
                                                    @csrf @method('PATCH')<button class="button button-danger button-small" type="submit">Cancel</button>
                                                </form>
                                            @endif
                                        </div></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($appointments->hasPages())<div class="pagination">{{ $appointments->links() }}</div>@endif
            @else
                <div class="empty-state">
                    <div class="empty-icon">⌕</div>
                    <strong>{{ auth()->user()->role === 'student' ? 'No consultation times have been assigned yet' : 'No consultation requests found' }}</strong>
                    @if (auth()->user()->role !== 'student')<p class="card-subtitle">Try changing your filters, or submit a new request.</p>@endif
                </div>
            @endif
        </section>
    </div>
@endsection
