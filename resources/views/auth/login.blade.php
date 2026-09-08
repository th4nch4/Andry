@extends('layouts.auth')

@section('title', 'Login')

@php
    // Set only when the throttle just locked this email+IP pair out.
    $lockoutSeconds = (int) session('lockout_seconds', 0);
    $locked = $lockoutSeconds > 0;
@endphp

@section('content')
    <h1>Login</h1>
    <p class="sub">Masuk ke akun Anda.</p>

    {{-- Confirmation coming back from the password reset flow. --}}
    @if (session('status'))
        <div class="alert alert-success" role="status">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 6 9 17l-5-5"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($locked)
        <div class="alert alert-danger" role="alert" id="lockout" data-seconds="{{ $lockoutSeconds }}">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect x="3" y="11" width="18" height="11" rx="2"/>
                <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
            </svg>
            <span>
                <strong>Terlalu banyak percobaan gagal.</strong>
                Login untuk akun ini dikunci. Coba lagi dalam
                <span class="countdown" id="lockout-countdown">{{ $lockoutSeconds }}</span> detik.
            </span>
        </div>
    @endif

    <form method="POST" action="{{ route('login') }}" id="login-form" novalidate>
        @csrf

        <div class="field">
            <label for="email">Alamat Email</label>
            <div class="control">
                <span class="icon" aria-hidden="true">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <path d="m2 7 10 6 10-6"/>
                    </svg>
                </span>
                <input id="email"
                       type="email"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="Email"
                       autocomplete="username"
                       inputmode="email"
                       required
                       autofocus
                       {{ $locked ? 'disabled' : '' }}
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            </div>
            {{-- While locked the banner above carries the message, so skip the duplicate. --}}
            @unless ($locked)
                @error('email')
                    <span class="field-error" id="email-error">{{ $message }}</span>
                @enderror
            @endunless
        </div>

        <div class="field">
            <label for="password">Kata Sandi</label>
            <div class="control">
                <span class="icon" aria-hidden="true">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </span>
                <input id="password"
                       class="has-reveal"
                       type="password"
                       name="password"
                       placeholder="Password"
                       autocomplete="current-password"
                       required
                       {{ $locked ? 'disabled' : '' }}
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button type="button"
                        class="reveal"
                        id="toggle-password"
                        aria-label="Tampilkan kata sandi"
                        aria-pressed="false"
                        {{ $locked ? 'disabled' : '' }}>
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <span class="field-error" id="password-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="row">
            <a class="link-sm" href="{{ route('password.request') }}">Lupa Password</a>
        </div>

        <button type="submit" class="btn" id="submit" {{ $locked ? 'disabled' : '' }}>
            {{ $locked ? 'Terkunci' : 'Login' }}
        </button>
    </form>
@endsection

@push('scripts')
<script>
    (function () {
        var toggle = document.getElementById('toggle-password');
        var password = document.getElementById('password');

        if (toggle && password) {
            toggle.addEventListener('click', function () {
                var hidden = password.type === 'password';
                password.type = hidden ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', String(hidden));
                toggle.setAttribute('aria-label', hidden ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
            });
        }

        // Count the lockout down, then hand the form back without a page reload.
        var lockout = document.getElementById('lockout');

        if (!lockout) {
            return;
        }

        var remaining = parseInt(lockout.getAttribute('data-seconds'), 10) || 0;
        var output = document.getElementById('lockout-countdown');
        var form = document.getElementById('login-form');
        var submit = document.getElementById('submit');

        var timer = setInterval(function () {
            remaining -= 1;

            if (remaining > 0) {
                output.textContent = remaining;
                return;
            }

            clearInterval(timer);
            lockout.parentNode.removeChild(lockout);

            var fields = form.querySelectorAll('input, button');

            for (var i = 0; i < fields.length; i++) {
                fields[i].disabled = false;
            }

            submit.textContent = 'Login';
            document.getElementById('email').focus();
        }, 1000);
    })();
</script>
@endpush
