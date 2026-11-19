@extends('layouts.app')

@section('title', 'Sign in | GuidanceSched')

@push('styles')
    <style>
        .content-wrap { width: 100%; max-width: none; margin: 0; padding: 0 !important; }
        .password-field { position: relative; }
        .password-field input { padding-right: 66px !important; }
        .password-toggle { position: absolute; top: 29px; right: 8px; min-height: 30px; padding: 5px 8px; border: 0; border-radius: 6px; color: #126b5b; background: transparent; cursor: pointer; font-size: 11px; font-weight: 700; }
        .password-toggle:hover { background: #eef6f3; }
        .password-toggle:focus-visible { outline: 2px solid #71b7a3; outline-offset: 2px; }
    </style>
@endpush

@section('content')
    <div class="auth-page">
        <section class="auth-panel">
            <div class="auth-card">
                <div class="auth-brand"><span class="brand-mark">G</span> GuidanceSched</div>
                <h1 class="auth-title">Welcome back</h1>
                <p class="auth-copy">Sign in to your school guidance workspace.</p>
                <form method="POST" action="{{ route('login.store') }}">
                    @csrf
                    <div class="field">
                        <label for="email">Email address</label>
                        <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus placeholder="name@school.edu">
                    </div>
                    <div class="field password-field">
                        <label for="password">Password</label>
                        <input id="password" type="password" name="password" autocomplete="current-password" required placeholder="Enter your password">
                        <button class="password-toggle" type="button" id="toggle-password" aria-controls="password" aria-pressed="false">Show</button>
                    </div>
                    <label class="checkbox-row"><input type="checkbox" name="remember" value="1"> Keep me signed in</label>
                    <button class="button" type="submit">Sign in</button>
                </form>
                <p class="field-help" style="margin-top:22px">Accounts are provided by your school administrator.</p>
            </div>
        </section>
        <aside class="auth-aside">
            <div class="auth-orb" aria-hidden="true">✳</div>
            <h2>Support starts with a conversation.</h2>
            <p>Coordinate student consultations and provide students with a clear view of their assigned date and time.</p>
        </aside>
    </div>
@endsection

@push('scripts')
    <script>
        document.getElementById('toggle-password')?.addEventListener('click', function () {
            const password = document.getElementById(this.getAttribute('aria-controls'));
            const isVisible = password.type === 'text';
            password.type = isVisible ? 'password' : 'text';
            this.textContent = isVisible ? 'Show' : 'Hide';
            this.setAttribute('aria-pressed', String(!isVisible));
            this.setAttribute('aria-label', isVisible ? 'Show password' : 'Hide password');
        });
    </script>
@endpush
