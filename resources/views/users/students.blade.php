@extends('layouts.app')

@section('title', 'Student profiles | GuidanceSched')
@section('page_title', 'Student profiles')
@section('page_description', 'View and update student placement, contact, and guardian details.')

@section('content')
    <section class="card">
        <div class="card-header">
            <div><h2 class="card-title">Students</h2><p class="card-subtitle">{{ $students->total() }} student accounts</p></div>
        </div>
        <form class="filter-bar" method="GET" action="{{ route('students.index') }}">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search student name or ID" aria-label="Search students">
            <button class="button button-secondary" type="submit">Search</button>
            @if (request()->filled('search'))<a class="button button-secondary" href="{{ route('students.index') }}">Clear</a>@endif
        </form>
        <div class="table-scroll">
            <table>
                <thead><tr><th>Student ID</th><th>Name</th><th>Grade</th><th>Section</th><th>Contact</th><th>Actions</th></tr></thead>
                <tbody>
                    @forelse ($students as $student)
                        <tr>
                            <td>{{ $student->student_number ?: '—' }}</td>
                            <td><span class="primary-text">{{ $student->name }}</span><span class="secondary-text">{{ $student->email }}</span></td>
                            <td>{{ $student->grade_level ?: '—' }}</td>
                            <td>{{ $student->section ?: '—' }}</td>
                            <td>{{ $student->contact_number ?: '—' }}</td>
                            <td><a class="button button-secondary button-small" href="{{ route('students.details.edit', $student) }}">Edit details</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6"><div class="empty-state"><div class="empty-icon">♙</div><strong>No students found</strong><p class="card-subtitle">@if (auth()->user()->role === 'teacher' && ! request()->filled('search'))Ask an administrator to assign students to your account.@else Try a different name or student ID.@endif</p></div></td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($students->hasPages())<div class="pagination">{{ $students->withQueryString()->links() }}</div>@endif
    </section>
@endsection
