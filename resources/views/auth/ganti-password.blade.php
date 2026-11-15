@extends('layouts.app')

@section('title', 'Ganti Password')
@section('heading', 'Ganti Password')
@section('description', 'Gunakan password baru minimal 8 karakter.')

@section('content')
    <section class="panel form-panel narrow-panel">
        <form method="POST" action="{{ route('password.update') }}" class="stack-form">
            @csrf
            @method('PUT')
            <label>Password lama<input type="password" name="password_lama" autocomplete="current-password" required></label>
            <label>Password baru<input type="password" name="password" autocomplete="new-password" minlength="8" required></label>
            <label>Konfirmasi password baru<input type="password" name="password_confirmation" autocomplete="new-password" minlength="8" required></label>
            <button class="button button-primary" type="submit">Simpan Password</button>
        </form>
    </section>
@endsection
