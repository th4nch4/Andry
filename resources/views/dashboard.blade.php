@extends('layouts.auth')

@section('title', 'Dashboard')

@section('content')
    <h1>Anda Berhasil Masuk</h1>
    <p class="sub">
        Selamat datang, <strong>{{ auth()->user()->name }}</strong>.
        Halaman ini berada di balik middleware <code>auth</code>, sehingga keluar akan menguncinya kembali.
    </p>

    <div class="alert alert-success" role="status">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M20 6 9 17l-5-5"/>
        </svg>
        <span>Masuk sebagai {{ auth()->user()->email }}</span>
    </div>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn">Keluar</button>
    </form>
@endsection
