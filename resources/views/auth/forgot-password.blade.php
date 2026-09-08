@extends('layouts.auth')

@section('title', 'Lupa Password')

@section('content')
    <h1>Lupa Password</h1>
    <p class="sub">
        Masukkan alamat email akun Anda. Kami akan mengirimkan tautan untuk membuat kata sandi baru.
    </p>

    @if (session('status'))
        <div class="alert alert-success" role="status">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                 stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M20 6 9 17l-5-5"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('password.email') }}" novalidate>
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
                       @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            </div>
            @error('email')
                <span class="field-error" id="email-error">{{ $message }}</span>
            @enderror
        </div>

        <button type="submit" class="btn">Kirim Tautan Reset</button>
    </form>

    <p class="foot">
        <a href="{{ route('login') }}">Kembali ke Login</a>
    </p>
@endsection
