<!DOCTYPE html>
{{-- Main layout shared by signed-in pages: navigation, page title, messages, and page content. --}}
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'GuidanceSched')</title>
    <style>
        :root {
            --ink: #152b2a;
            --muted: #6f8280;
            --line: #e5ecea;
            --canvas: #f4f7f6;
            --surface: #fff;
            --brand: #126b5b;
            --brand-dark: #0e5549;
            --sidebar: #132f2d;
            --sidebar-muted: #a8bfbb;
            --danger: #a43c39;
            --success: #176a4e;
            --shadow: 0 8px 28px rgba(21, 55, 50, .06);
        }
        * { box-sizing: border-box; }
        body { margin: 0; background: var(--canvas); color: var(--ink); font: 15px/1.5 Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        a { color: var(--brand); text-decoration: none; }
        a:hover { color: var(--brand-dark); }
        button, input, select, textarea { font: inherit; }
        .app-sidebar { position: fixed; inset: 0 auto 0 0; z-index: 5; display: flex; flex-direction: column; width: 252px; padding: 24px 16px 18px; color: white; background: var(--sidebar); }
        .brand-lockup { display: flex; align-items: center; gap: 11px; padding: 2px 10px 27px; color: white; }
        .mobile-menu-toggle { display: none; }
        .brand-mark { display: grid; place-items: center; width: 38px; height: 38px; border-radius: 12px; color: #12312e; background: #88d0bc; font-size: 17px; font-weight: 800; }
        .brand-name { font-size: 16px; font-weight: 750; letter-spacing: -.02em; }
        .brand-caption { color: #a7c2bc; font-size: 11px; }
        .nav-label { padding: 12px 11px 7px; color: #86a49e; font-size: 10px; font-weight: 750; letter-spacing: .12em; text-transform: uppercase; }
        .side-nav { display: grid; gap: 5px; }
        .side-link { display: flex; align-items: center; gap: 12px; min-height: 43px; padding: 10px 12px; border-radius: 9px; color: var(--sidebar-muted); font-size: 13px; font-weight: 550; transition: .15s ease; }
        .side-link:hover { color: white; background: rgba(255,255,255,.07); }
        .side-link.active { color: white; background: #20574e; box-shadow: inset 3px 0 #8bd7c1; }
        .side-icon { display: grid; place-items: center; width: 19px; height: 19px; opacity: .9; }
        .side-icon svg { width: 18px; height: 18px; fill: none; stroke: currentColor; stroke-width: 1.7; stroke-linecap: round; stroke-linejoin: round; }
        .sidebar-spacer { flex: 1; }
        .account-card { display: flex; align-items: center; gap: 10px; padding: 13px 9px; border-top: 1px solid rgba(255,255,255,.12); }
        .avatar { display: grid; flex: 0 0 36px; place-items: center; width: 36px; height: 36px; border-radius: 50%; color: #143a34; background: #c3e8dc; font-size: 13px; font-weight: 750; }
        .account-name { overflow: hidden; color: white; font-size: 12px; font-weight: 650; text-overflow: ellipsis; white-space: nowrap; }
        .account-role { color: #a8bfbb; font-size: 11px; }
        .logout-form { padding: 0 9px; }
        .logout-button { width: 100%; padding: 9px 11px; border: 1px solid rgba(255,255,255,.16); border-radius: 8px; color: #d9e7e4; background: transparent; cursor: pointer; text-align: left; font-size: 12px; }
        .logout-button:hover { border-color: rgba(255,255,255,.34); background: rgba(255,255,255,.06); }
        .app-main { min-height: 100vh; margin-left: 252px; }
        .topbar { display: flex; align-items: center; justify-content: space-between; gap: 20px; min-height: 106px; padding: 25px clamp(24px, 4vw, 58px); border-bottom: 1px solid #e9efed; background: rgba(255,255,255,.76); }
        .page-title { margin: 0; font-size: clamp(21px, 2vw, 27px); font-weight: 720; letter-spacing: -.035em; }
        .page-description { margin: 4px 0 0; color: var(--muted); font-size: 13px; }
        .topbar-meta { color: var(--muted); font-size: 12px; }
        .content-wrap { width: min(1380px, 100%); margin: 0 auto; padding: 28px clamp(24px, 4vw, 58px) 48px; }
        .content-stack { display: grid; gap: 20px; }
        .content-grid { display: grid; grid-template-columns: minmax(280px, .82fr) minmax(0, 1.5fr); align-items: start; gap: 20px; }
        .card { min-width: 0; padding: 22px; border: 1px solid #e8eeec; border-radius: 13px; background: var(--surface); box-shadow: var(--shadow); }
        .card-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 15px; margin-bottom: 17px; }
        .card-title { margin: 0; font-size: 16px; font-weight: 700; letter-spacing: -.015em; }
        .card-subtitle { margin: 5px 0 0; color: var(--muted); font-size: 12px; }
        .stat-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 15px; }
        .stat-card { position: relative; overflow: hidden; padding: 20px; border: 1px solid #e8eeec; border-radius: 13px; background: white; box-shadow: var(--shadow); }
        .stat-label { color: var(--muted); font-size: 12px; font-weight: 550; }
        .stat-value { margin: 11px 0 2px; font-size: 30px; font-weight: 750; letter-spacing: -.04em; }
        .stat-note { color: #8a9997; font-size: 11px; }
        .stat-accent { position: absolute; top: 19px; right: 19px; width: 9px; height: 9px; border-radius: 50%; background: #68b69f; box-shadow: 0 0 0 5px #e7f5f0; }
        .button { display: inline-flex; align-items: center; justify-content: center; gap: 7px; min-height: 38px; padding: 9px 13px; border: 1px solid transparent; border-radius: 8px; color: white; background: var(--brand); cursor: pointer; font-size: 12px; font-weight: 650; transition: .15s ease; }
        .button:hover { color: white; background: var(--brand-dark); }
        .button-secondary { border-color: #dce6e3; color: #37514d; background: white; }
        .button-secondary:hover { border-color: #bdd0cb; color: var(--ink); background: #f8fbfa; }
        .button-danger { border-color: #f1d8d7; color: var(--danger); background: #fff8f7; }
        .button-danger:hover { color: #8d302d; background: #ffefed; }
        .button-small { min-height: 31px; padding: 6px 9px; font-size: 11px; }
        .form-grid { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 14px; }
        .field { min-width: 0; }
        .field-full { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 6px; color: #3b514e; font-size: 12px; font-weight: 650; }
        input:not([type=checkbox]), select, textarea { display: block; width: 100%; min-height: 40px; padding: 9px 11px; border: 1px solid #dce6e3; border-radius: 8px; outline: none; color: var(--ink); background: #fff; font-size: 13px; transition: border-color .15s, box-shadow .15s; }
        textarea { min-height: 100px; resize: vertical; }
        input:focus, select:focus, textarea:focus { border-color: #71b7a3; box-shadow: 0 0 0 3px rgba(59, 151, 125, .12); }
        .field-help { margin-top: 5px; color: #899895; font-size: 11px; }
        .filter-bar { display: flex; align-items: center; gap: 9px; flex-wrap: wrap; margin: 0 0 18px; }
        .filter-bar input { flex: 1 1 230px; }
        .filter-bar select { flex: 0 1 170px; }
        .table-scroll { overflow-x: auto; width: 100%; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 11px 13px; border-bottom: 1px solid var(--line); color: #778985; font-size: 10px; font-weight: 700; letter-spacing: .07em; text-transform: uppercase; white-space: nowrap; }
        td { padding: 14px 13px; border-bottom: 1px solid #edf1f0; color: #304441; font-size: 12px; vertical-align: middle; }
        tbody tr:last-child td { border-bottom: 0; }
        tbody tr:hover { background: #fbfdfc; }
        .primary-text { color: var(--ink); font-weight: 650; }
        .secondary-text { display: block; margin-top: 3px; color: var(--muted); font-size: 11px; }
        .badge { display: inline-flex; align-items: center; gap: 6px; padding: 5px 9px; border-radius: 20px; color: #49615d; background: #eef3f1; font-size: 10px; font-weight: 700; white-space: nowrap; }
        .badge::before { width: 6px; height: 6px; border-radius: 50%; background: currentColor; content: ""; }
        .badge-pending { color: #94641a; background: #fff4dd; }
        .badge-approved, .badge-open { color: #176b56; background: #e6f5ef; }
        .badge-completed, .badge-closed { color: #526c92; background: #ecf2fb; }
        .badge-cancelled { color: #9a4541; background: #fcebea; }
        .badge-in_progress { color: #7560a4; background: #f1edfb; }
        .actions { display: flex; align-items: center; gap: 7px; flex-wrap: wrap; }
        .inline-form { display: inline-flex; margin: 0; }
        .notice { margin-bottom: 18px; padding: 12px 15px; border: 1px solid #d0eadf; border-radius: 9px; color: #24694c; background: #f0faf5; font-size: 12px; }
        .notice-error { border-color: #f0d5d3; color: #913b37; background: #fff6f5; }
        .notice ul { margin: 6px 0 0; padding-left: 20px; }
        .empty-state { padding: 34px 20px; color: var(--muted); text-align: center; }
        .empty-icon { display: grid; place-items: center; width: 42px; height: 42px; margin: 0 auto 12px; border-radius: 13px; color: #438b77; background: #e9f5f1; font-size: 20px; }
        .pagination { margin-top: 18px; }
        .pagination nav { width: 100%; }
        .pagination nav > div:first-child { display: none; }
        .pagination nav > div:last-child { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 12px; }
        .pagination nav > div:last-child > div:last-child > span { display: flex; flex-wrap: wrap; align-items: center; gap: 5px; box-shadow: none; }
        .pagination nav a,
        .pagination nav [aria-disabled="true"] > span,
        .pagination nav [aria-current="page"] > span {
            box-sizing: border-box;
            display: inline-flex;
            min-width: 36px;
            min-height: 36px;
            align-items: center;
            justify-content: center;
            padding: 7px 10px;
            border: 1px solid #e2eae7;
            border-radius: 7px;
            color: #37514d;
            background: white;
            font-size: 12px;
            line-height: 20px;
        }
        .pagination nav a:hover { color: var(--brand-dark); background: #f8fbfa; }
        .pagination nav [aria-disabled="true"] > span,
        .pagination nav [aria-current="page"] > span { color: var(--muted); background: #f4f7f6; }
        .pagination nav svg { width: 16px; height: 16px; flex: none; }
        @media (max-width: 560px) {
            .pagination nav > div:last-child > div:first-child { display: none; }
            .pagination nav > div:last-child > div:last-child { width: 100%; }
            .pagination nav > div:last-child > div:last-child > span { justify-content: center; }
        }
        .detail-list { display: grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap: 15px; margin: 0; }
        .detail-item dt { color: var(--muted); font-size: 11px; }
        .detail-item dd { margin: 4px 0 0; color: var(--ink); font-size: 13px; font-weight: 600; }
        .note-text { white-space: pre-wrap; }
        .follow-up-item { padding: 16px 0; border-top: 1px solid var(--line); }
        .follow-up-item:first-of-type { border-top: 0; }
        .follow-up-date { color: #47645e; font-size: 11px; font-weight: 700; }
        .follow-up-edit { margin-top: 10px; }
        .follow-up-edit summary { display: inline-flex; cursor: pointer; list-style: none; }
        .follow-up-edit summary::-webkit-details-marker { display: none; }
        .follow-up-edit-form { margin-top: 12px; }
        .auth-page { display: grid; min-height: 100vh; grid-template-columns: minmax(350px, .9fr) 1.1fr; background: #fff; }
        .auth-panel { display: flex; align-items: center; justify-content: center; padding: 48px 30px; }
        .auth-card { width: min(390px, 100%); }
        .auth-brand { display: flex; align-items: center; gap: 10px; margin-bottom: 40px; color: var(--ink); font-size: 15px; font-weight: 750; }
        .auth-title { margin: 0; font-size: 27px; letter-spacing: -.04em; }
        .auth-copy { margin: 8px 0 25px; color: var(--muted); font-size: 13px; }
        .auth-card .field { margin: 14px 0; }
        .auth-card .button { width: 100%; min-height: 43px; margin-top: 9px; }
        .auth-aside { display: flex; flex-direction: column; justify-content: center; min-height: 100vh; padding: 10%; color: white; background: radial-gradient(circle at 75% 25%, rgba(115,190,165,.3), transparent 30%), linear-gradient(145deg, #123b35, #0d2828); }
        .auth-aside h2 { max-width: 520px; margin: 0; font-size: clamp(32px, 4vw, 54px); line-height: 1.06; letter-spacing: -.05em; }
        .auth-aside p { max-width: 440px; margin-top: 18px; color: #b8d2cb; font-size: 15px; }
        .auth-orb { display: grid; place-items: center; width: 90px; height: 90px; margin-bottom: 34px; border: 1px solid rgba(190,236,220,.35); border-radius: 26px; color: #c1eadb; background: rgba(255,255,255,.08); font-size: 36px; }
        .checkbox-row { display: flex; align-items: center; gap: 9px; color: var(--muted); font-size: 12px; }
        .checkbox-row input { accent-color: var(--brand); }
        @media (max-width: 980px) {
            .stat-grid { grid-template-columns: repeat(2, minmax(0,1fr)); }
            .content-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 720px) {
            .app-sidebar { position: sticky; top: 0; width: 100%; padding: 10px 14px; }
            .brand-lockup { padding: 3px 5px; }
            .brand-mark { width: 33px; height: 33px; }
            .mobile-menu-toggle { display: grid; place-items: center; width: 44px; height: 44px; border: 1px solid rgba(255,255,255,.2); border-radius: 9px; color: white; background: transparent; cursor: pointer; font-size: 23px; line-height: 1; }
            .mobile-menu-toggle:focus-visible { outline: 2px solid #8bd7c1; outline-offset: 2px; }
            .app-sidebar:not(.is-open) .nav-label,
            .app-sidebar:not(.is-open) .side-nav,
            .app-sidebar:not(.is-open) .sidebar-spacer,
            .app-sidebar:not(.is-open) .account-card,
            .app-sidebar:not(.is-open) .logout-form { display: none; }
            .app-sidebar.is-open .nav-label { display: block; }
            .app-sidebar.is-open .side-nav { display: grid; gap: 4px; padding: 0 0 8px; }
            .app-sidebar.is-open .sidebar-spacer { display: none; }
            .app-sidebar.is-open .account-card { display: flex; margin-top: 4px; }
            .app-sidebar.is-open .logout-form { display: block; }
            .side-link { min-height: 42px; padding: 9px 11px; font-size: 13px; }
            .side-link.active { box-shadow: inset 3px 0 #8bd7c1; }
            .side-icon { display: grid; }
            .app-main { margin-left: 0; }
            .topbar { min-height: 82px; padding: 17px 18px; }
            .content-wrap { padding: 18px 14px 34px; }
            .card { padding: 17px; }
            .auth-page { grid-template-columns: 1fr; }
            .auth-aside { display: none; }
            .auth-panel { min-height: 100vh; padding: 32px 22px; }
        }
        @media (max-width: 460px) {
            .stat-grid { gap: 9px; }
            .stat-card { padding: 14px; }
            .stat-value { font-size: 25px; }
            .form-grid, .detail-list { grid-template-columns: 1fr; }
            .field-full { grid-column: auto; }
        }
    </style>
    @stack('styles')
</head>
<body>
@auth
    @php($currentUser = auth()->user())
    <aside class="app-sidebar" id="app-navigation">
        <div style="display:flex;align-items:center;justify-content:space-between;gap:10px">
            <a class="brand-lockup" href="{{ route('dashboard') }}">
                <span class="brand-mark">G</span>
                <span><span class="brand-name">GuidanceSched</span><br><span class="brand-caption">Student support portal</span></span>
            </a>
            <button class="mobile-menu-toggle" type="button" aria-label="Open navigation menu" aria-expanded="false" aria-controls="app-navigation-content">☰</button>
        </div>
        <div id="app-navigation-content">
        <div class="nav-label">Workspace</div>
        <nav class="side-nav" aria-label="Main navigation">
            <a class="side-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                <span class="side-icon"><svg viewBox="0 0 24 24"><rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/></svg></span>Overview
            </a>
            <a class="side-link {{ request()->routeIs('appointments.*') ? 'active' : '' }}" href="{{ route('appointments.index') }}">
                <span class="side-icon"><svg viewBox="0 0 24 24"><rect x="3.5" y="5" width="17" height="16" rx="2"/><path d="M7.5 3.5v3M16.5 3.5v3M3.5 9.5h17M8 13h3M8 16.5h6"/></svg></span>Consultations
            </a>
            @if ($currentUser->role === 'teacher')
                <a class="side-link {{ request()->routeIs('students.*') ? 'active' : '' }}" href="{{ route('students.index') }}">
                    <span class="side-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.8 20a6.2 6.2 0 0 1 12.4 0M17 8h4M19 6v4M16.5 15.5a5.2 5.2 0 0 1 4.7 4.5"/></svg></span>Student profiles
                </a>
            @endif
            @if ($currentUser->role === 'admin')
                <div class="nav-label">Guidance</div>
                <a class="side-link {{ request()->routeIs('cases.*') ? 'active' : '' }}" href="{{ route('cases.index') }}">
                    <span class="side-icon"><svg viewBox="0 0 24 24"><path d="M4 6.5A2.5 2.5 0 0 1 6.5 4H10l2 2h5.5A2.5 2.5 0 0 1 20 8.5v9a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 17.5z"/><path d="M8 12h8M8 15.5h5"/></svg></span>Case records
                </a>
                <div class="nav-label">Administration</div>
                <a class="side-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
                    <span class="side-icon"><svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3.5"/><path d="M2.8 20a6.2 6.2 0 0 1 12.4 0M16 5.5a3.5 3.5 0 0 1 0 6.8M17 14.5a5.2 5.2 0 0 1 4.2 5"/></svg></span>User accounts
                </a>
            @endif
        </nav>
        <div class="sidebar-spacer"></div>
        <div class="account-card">
            <div class="avatar">{{ mb_substr($currentUser->name, 0, 1) }}</div>
            <div style="min-width:0"><div class="account-name">{{ $currentUser->name }}</div><div class="account-role">{{ $currentUser->role === 'admin' ? 'Administrator / Guidance' : ucfirst($currentUser->role) }}</div></div>
        </div>
        <form class="logout-form" method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="logout-button" type="submit">↪ &nbsp; Sign out</button>
        </form>
        </div>
    </aside>
@endauth

<div class="app-main">
    @auth
        <header class="topbar">
            <div>
                <h1 class="page-title">@yield('page_title', 'Overview')</h1>
                <p class="page-description">@yield('page_description', 'A clear view of your guidance center workspace.')</p>
            </div>
            <div class="topbar-meta">{{ now()->format('D, M j') }}</div>
        </header>
    @endauth
    <main class="content-wrap">
        @if (session('success'))
            <div class="notice" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="notice notice-error" role="alert">
                <strong>Please review the following:</strong>
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif
        @yield('content')
    </main>
</div>
<script>
    const mobileMenuToggle = document.querySelector('.mobile-menu-toggle');
    const appNavigation = document.querySelector('.app-sidebar');
    mobileMenuToggle?.addEventListener('click', () => {
        const isOpen = appNavigation.classList.toggle('is-open');
        mobileMenuToggle.setAttribute('aria-expanded', String(isOpen));
        mobileMenuToggle.setAttribute('aria-label', isOpen ? 'Close navigation menu' : 'Open navigation menu');
        mobileMenuToggle.textContent = isOpen ? '×' : '☰';
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && appNavigation?.classList.contains('is-open')) {
            appNavigation.classList.remove('is-open');
            mobileMenuToggle?.setAttribute('aria-expanded', 'false');
            mobileMenuToggle?.setAttribute('aria-label', 'Open navigation menu');
            if (mobileMenuToggle) mobileMenuToggle.textContent = '☰';
            mobileMenuToggle?.focus();
        }
    });

    window.setupAvailableSlotPicker = function (dateSelect, timeSelect, endpoint, selectedTime = '', requireSelection = false, appointmentId = null) {
        let requestNumber = 0;
        const refresh = async () => {
            const currentRequest = ++requestNumber;
            const date = dateSelect.value;
            const previousSelection = selectedTime || timeSelect.value;
            timeSelect.replaceChildren(new Option('Choose a time', ''));
            timeSelect.disabled = true;
            timeSelect.required = requireSelection || Boolean(date);
            if (!date) return;

            const params = new URLSearchParams({ date });
            if (appointmentId) params.set('appointment_id', appointmentId);
            try {
                const response = await fetch(`${endpoint}?${params}`, { headers: { Accept: 'application/json' }, cache: 'no-store' });
                if (currentRequest !== requestNumber || dateSelect.value !== date) return;
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const { times } = await response.json();
                if (currentRequest !== requestNumber || dateSelect.value !== date) return;
                if (times.length === 0) timeSelect.options[0].text = 'No open times for this date';
                times.forEach((time) => timeSelect.add(new Option(time.label, time.value, false, time.value === previousSelection)));
                timeSelect.disabled = false;
                selectedTime = '';
            } catch (error) {
                if (currentRequest !== requestNumber) return;
                timeSelect.replaceChildren(new Option(`Unable to load available times${error.message ? ` (${error.message})` : ''}`, ''));
            }
        };
        dateSelect.addEventListener('change', () => {
            selectedTime = '';
            refresh();
        });
        refresh();
    };
</script>
@stack('scripts')
</body>
</html>
