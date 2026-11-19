@extends('layouts.app')

@section('title', 'User accounts | GuidanceSched')
@section('page_title', 'User accounts')
@section('page_description', 'Manage student, teacher, and guidance administrator access.')

@section('content')
    <section class="card">
        <div class="card-header">
            <div>
                <h2 class="card-title">All accounts</h2>
                <p class="card-subtitle">{{ $users->total() }} registered users</p>
            </div>
            <a class="button" href="{{ route('users.create') }}">+ Add user</a>
        </div>

        <form class="filter-bar" method="GET" action="{{ route('users.index') }}">
            <input name="search" type="search" value="{{ request('search') }}" placeholder="Search by name or email" aria-label="Search users">
            <select name="role" aria-label="Filter by role">
                <option value="">All roles</option>
                @foreach (['admin' => 'Administrator / Guidance', 'teacher' => 'Teacher', 'student' => 'Student'] as $value => $label)
                    <option value="{{ $value }}" @selected(request('role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <button class="button button-secondary" type="submit">Filter</button>
            @if (request()->hasAny(['search', 'role']))<a class="button button-secondary" href="{{ route('users.index') }}">Clear</a>@endif
        </form>

        <div class="table-scroll">
            <table>
                <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($users as $user)
                    <tr>
                        <td class="primary-text">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td><span class="badge">{{ $user->role === 'admin' ? 'Administrator / Guidance' : ucfirst($user->role) }}</span></td>
                        <td>{{ $user->created_at?->format('M j, Y') ?? '—' }}</td>
                        <td>
                            <div class="actions">
                                <a class="button button-secondary button-small" href="{{ route('users.edit', $user) }}">Edit</a>
                                @if ($user->id !== auth()->id())
                                    <form class="inline-form" method="POST" action="{{ route('users.destroy', $user) }}" onsubmit="return confirm('Delete this user account?')">
                                        @csrf @method('DELETE')
                                        <button class="button button-danger button-small" type="submit">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="empty-state"><div class="empty-icon">♙</div><strong>No user accounts found</strong><div>Try changing your search or role filter.</div></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())<div class="pagination">{{ $users->withQueryString()->links() }}</div>@endif
    </section>
@endsection
