@extends('layouts.app')

@section('title', 'Reset Password Pelanggan')
@section('heading', 'Reset Password Pelanggan')
@section('description', 'Buat password sementara untuk pelanggan yang lupa password.')

@section('content')
    <div class="reset-password-workspace">
        <section class="panel form-panel">
            <h2>Cari akun pelanggan</h2>
            <p class="panel-hint">Masukkan nomor HP pelanggan untuk memeriksa akun sebelum reset.</p>
            <form method="POST" action="{{ route('kasir.reset-password.simpan') }}" class="stack-form">
                @csrf
                <input type="hidden" name="aksi" value="cari">
                <label>Nomor HP pelanggan<input type="tel" name="no_hp" value="{{ old('no_hp') }}" inputmode="tel" maxlength="20" required></label>
                <button class="button button-secondary" type="submit">Cari akun</button>
            </form>
        </section>
        <section class="panel form-panel">
            <h2>Hasil</h2>
            @if (session('password_sementara'))
                <p>Akun ditemukan: {{ session('reset_pelanggan') }}</p>
                <p class="panel-hint">Password sementara (ditampilkan satu kali):</p>
                <code class="temporary-password">{{ session('password_sementara') }}</code>
                <p class="panel-hint">Pelanggan wajib mengganti password saat login berikutnya.</p>
            @elseif ($pelanggan)
                <p>Akun ditemukan: {{ $pelanggan->no_hp }}</p>
                <form method="POST" action="{{ route('kasir.reset-password.simpan') }}" class="stack-form">
                    @csrf
                    <input type="hidden" name="no_hp" value="{{ $pelanggan->no_hp }}">
                    <input type="hidden" name="aksi" value="reset">
                    <button class="button button-primary" type="submit">Reset menjadi password sementara</button>
                </form>
            @else
                <p class="panel-hint">Hasil pencarian akun akan muncul di sini.</p>
            @endif
        </section>
    </div>
@endsection
