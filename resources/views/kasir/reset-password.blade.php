@extends('layouts.app')

@section('title', 'Reset Password Pelanggan')
@section('heading', 'Reset Password Pelanggan')
@section('description', 'Buat password sementara untuk pelanggan yang lupa password.')

@section('content')
    <section class="panel form-panel narrow-panel">
        @if (session('password_sementara'))
            <div class="alert alert-success">
                Password sementara untuk <strong>{{ session('reset_pelanggan') }}</strong> (ditampilkan satu kali):
                <code class="temporary-password">{{ session('password_sementara') }}</code>
            </div>
        @endif
        <form method="POST" action="{{ route('kasir.reset-password.simpan') }}" class="stack-form">
            @csrf
            <label>Nomor HP pelanggan<input type="tel" name="no_hp" value="{{ old('no_hp') }}" inputmode="tel" maxlength="20" required></label>
            <button class="button button-primary" type="submit">Buat Password Sementara</button>
        </form>
    </section>
@endsection
