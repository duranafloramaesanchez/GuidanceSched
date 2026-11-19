@extends('layouts.app')

@section('title', 'Case records | GuidanceSched')
@section('page_title', 'Case records')
@section('page_description', 'Confidential case records for authorized guidance staff.')

@section('content')
    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">Case register</h2>
                <p class="card-subtitle">{{ $cases->total() }} {{ $cases->total() === 1 ? 'record' : 'records' }} · Authorized staff only</p>
            </div>
            <a class="button" href="{{ route('cases.create') }}">＋ Create case</a>
        </div>

        <form class="filter-bar" method="GET">
            <input type="search" name="search" value="{{ request('search') }}" placeholder="Search case number, title, or student" aria-label="Search cases">
            <select name="status" aria-label="Filter by status">
                <option value="">All statuses</option>
                @foreach (['open', 'in_progress', 'closed'] as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                @endforeach
            </select>
            <select name="category" aria-label="Filter by category">
                <option value="">All categories</option>
                @foreach (['academic', 'personal', 'social', 'career', 'other'] as $category)
                    <option value="{{ $category }}" @selected(request('category') === $category)>{{ ucfirst($category) }}</option>
                @endforeach
            </select>
            <button class="button" type="submit">Apply filters</button>
            @if (request()->hasAny(['search', 'status', 'category']))
                <a class="button button-secondary" href="{{ route('cases.index') }}">Clear</a>
            @endif
        </form>

        @if ($cases->count())
            <div class="table-scroll">
                <table>
                    <thead><tr><th>Case</th><th>Student</th><th>Category</th><th>Status</th><th>Created</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($cases as $case)
                        <tr>
                            <td><a class="primary-text" href="{{ route('cases.show', $case) }}">{{ $case->case_number }}</a><span class="secondary-text">{{ $case->title }}</span></td>
                            <td>{{ $case->student?->name ?? 'Unavailable' }}</td>
                            <td><span class="badge">{{ ucfirst($case->category) }}</span></td>
                            <td><span class="badge badge-{{ $case->status }}">{{ ucfirst(str_replace('_', ' ', $case->status)) }}</span></td>
                            <td>{{ $case->created_at->format('M d, Y') }}</td>
                            <td><a class="button button-secondary button-small" href="{{ route('cases.show', $case) }}">Open</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">{{ $cases->links() }}</div>
        @else
            <div class="empty-state">
                <div class="empty-icon">▤</div>
                <strong>No case records found</strong>
                <p class="card-subtitle">Create a case or adjust your search filters.</p>
            </div>
        @endif
    </section>
@endsection
