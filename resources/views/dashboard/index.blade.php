@extends('layouts.app')

@section('title', 'Overview | GuidanceSched')
@section('page_title', 'Good day, ' . $user->name)
@section('page_description', 'Here is a quick look at your guidance center activity.')

@section('content')
    <div class="content-stack">
        <section class="stat-grid" aria-label="Activity summary">
            <article class="stat-card">
                <span class="stat-accent"></span>
                <div class="stat-label">Pending</div>
                <p class="stat-value">{{ $stats['pending'] }}</p>
                <div class="stat-note">Awaiting administrator approval</div>
            </article>
            <article class="stat-card">
                <span class="stat-accent" style="background:#48a986;box-shadow:0 0 0 5px #e7f5f0"></span>
                <div class="stat-label">Approved</div>
                <p class="stat-value">{{ $stats['approved'] }}</p>
                <div class="stat-note">Accepted consultations</div>
            </article>
            <article class="stat-card">
                <span class="stat-accent" style="background:#799bd0;box-shadow:0 0 0 5px #edf2fb"></span>
                <div class="stat-label">Completed</div>
                <p class="stat-value">{{ $stats['completed'] }}</p>
                <div class="stat-note">Consultations completed</div>
            </article>
            <article class="stat-card">
                <span class="stat-accent" style="background:#c56e69;box-shadow:0 0 0 5px #fcebea"></span>
                <div class="stat-label">Cancelled</div>
                <p class="stat-value">{{ $stats['cancelled'] }}</p>
                <div class="stat-note">Cancelled consultations</div>
            </article>
            @if ($user->role === 'admin')
                <article class="stat-card">
                    <span class="stat-accent" style="background:#a88ad2;box-shadow:0 0 0 5px #f3effa"></span>
                    <div class="stat-label">Active cases</div>
                    <p class="stat-value">{{ $stats['open_cases'] }}</p>
                    <div class="stat-note">Open or in progress</div>
                </article>
            @endif
        </section>

        <div class="content-grid">
            <section class="card">
                <div class="card-header">
                    <div>
                        <h2 class="card-title">Quick actions</h2>
                        <p class="card-subtitle">Jump back into your workspace.</p>
                    </div>
                </div>
                <div class="content-stack" style="gap:10px">
                    <a class="button" href="{{ route('appointments.index') }}">{{ $user->role === 'student' ? 'View assigned schedule' : 'View consultations' }} <span aria-hidden="true">→</span></a>
                    @if ($user->role === 'admin')
                        <a class="button button-secondary" href="{{ route('cases.create') }}">Create a case record</a>
                        <a class="button button-secondary" href="{{ route('users.create') }}">Add a user account</a>
                    @elseif ($user->role === 'teacher')
                        <a class="button button-secondary" href="{{ route('appointments.index') }}">Create a student request</a>
                        <a class="button button-secondary" href="{{ route('students.index') }}">Update student profiles</a>
                    @endif
                </div>
            </section>

            @if ($user->role === 'admin')
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Guidance follow-ups</h2>
                            <p class="card-subtitle">Dated follow-up entries recorded today.</p>
                        </div>
                        <span class="badge badge-approved">{{ $stats['follow_ups_today'] }} today</span>
                    </div>
                    <p class="page-description">Open a case record to review its private notes and follow-up history.</p>
                    <p style="margin:18px 0 0"><a class="button button-secondary" href="{{ route('cases.index') }}">Open case records</a></p>
                </section>
            @elseif ($user->role === 'teacher')
                <section class="card">
                    <div class="card-header"><div><h2 class="card-title">Consultation coordination</h2><p class="card-subtitle">Assign open time slots to pending student requests.</p></div></div>
                    <p class="page-description">Assigned times reserve the slot. Only administrators can approve or change request status.</p>
                    <p style="margin:18px 0 0"><a class="button button-secondary" href="{{ route('appointments.index') }}">Review requests</a></p>
                </section>
            @else
                <section class="card">
                    <div class="card-header">
                        <div>
                            <h2 class="card-title">Your consultation times</h2>
                            <p class="card-subtitle">Your assigned consultation topic, date, time, and status.</p>
                        </div>
                    </div>
                    <p class="page-description">Teachers and administrators create requests for students. Your schedule shows the consultation topic, assigned date and time, and status.</p>
                </section>
            @endif
        </div>
    </div>
@endsection
