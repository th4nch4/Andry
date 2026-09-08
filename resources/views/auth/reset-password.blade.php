@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <h1>Buat Kata Sandi Baru</h1>
    <p class="sub">Pilih kata sandi yang belum pernah Anda gunakan. Minimal delapan karakter.</p>

    <form method="POST" action="{{ route('password.update') }}" novalidate>
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">

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
                       value="{{ old('email', $email) }}"
                       autocomplete="username"
                       required
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            </div>
            @error('email')
                <span class="field-error" id="email-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label for="password">Kata Sandi Baru</label>
            <div class="control">
                <span class="icon" aria-hidden="true">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </span>
                <input id="password"
                       type="password"
                       name="password"
                       placeholder="Minimal 8 karakter"
                       autocomplete="new-password"
                       required
                       autofocus
                       @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
            </div>
            @error('password')
                <span class="field-error" id="password-error">{{ $message }}</span>
            @enderror
        </div>

        <div class="field">
            <label for="password_confirmation">Konfirmasi Kata Sandi Baru</label>
            <div class="control">
                <span class="icon" aria-hidden="true">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                         stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2"/>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                    </svg>
                </span>
                <input id="password_confirmation"
                       type="password"
                       name="password_confirmation"
                       placeholder="Ulangi kata sandi baru"
                       autocomplete="new-password"
                       required>
            </div>
        </div>

        <button type="submit" class="btn">Reset Kata Sandi</button>
    </form>

    <p class="foot">
        <a href="{{ route('login') }}">Kembali ke Login</a>
    </p>
@endsection
