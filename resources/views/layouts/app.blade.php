<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Kedai Kopi Senja') | Kedai Kopi Senja</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div class="app-shell">
        <aside class="sidebar" aria-label="Navigasi utama">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-mark" aria-hidden="true">S</span>
                <span>
                    <span class="brand-name">Kedai Kopi Senja</span>
                    <span class="brand-caption">Sistem Informasi Kafe</span>
                </span>
            </a>

            @if (auth()->user()->role === 'Kasir')
                <div class="nav-label">Transaksi</div>
                <nav class="nav-list">
                    <a class="nav-link {{ request()->routeIs('kasir.pesanan.baru') ? 'active' : '' }}" href="{{ route('kasir.pesanan.baru') }}"><span class="nav-icon">+</span>Pesanan Baru</a>
                    <a class="nav-link {{ request()->routeIs('kasir.pesanan.index', 'kasir.struk') ? 'active' : '' }}" href="{{ route('kasir.pesanan.index') }}"><span class="nav-icon">&#9776;</span>Daftar Pesanan</a>
                    <a class="nav-link {{ request()->routeIs('kasir.menu') ? 'active' : '' }}" href="{{ route('kasir.menu') }}"><span class="nav-icon">&#9634;</span>Daftar Menu</a>
                    <a class="nav-link {{ request()->routeIs('kasir.reset-password*') ? 'active' : '' }}" href="{{ route('kasir.reset-password') }}"><span class="nav-icon">&#8635;</span>Reset Password</a>
                </nav>
            @else
                <div class="nav-label">Operasional</div>
                <nav class="nav-list">
                    <a class="nav-link {{ request()->routeIs('manajer.bahan') ? 'active' : '' }}" href="{{ route('manajer.bahan') }}"><span class="nav-icon">&#9671;</span>Bahan</a>
                    <a class="nav-link {{ request()->routeIs('manajer.menu') ? 'active' : '' }}" href="{{ route('manajer.menu') }}"><span class="nav-icon">&#9634;</span>Menu</a>
                    <a class="nav-link {{ request()->routeIs('manajer.resep') ? 'active' : '' }}" href="{{ route('manajer.resep') }}"><span class="nav-icon">&#9776;</span>Resep</a>
                    <a class="nav-link {{ request()->routeIs('manajer.stok-masuk') ? 'active' : '' }}" href="{{ route('manajer.stok-masuk') }}"><span class="nav-icon">&#8595;</span>Stok Masuk</a>
                    <a class="nav-link {{ request()->routeIs('manajer.batas-minimum') ? 'active' : '' }}" href="{{ route('manajer.batas-minimum') }}"><span class="nav-icon">&#9888;</span>Batas Minimum</a>
                    <a class="nav-link {{ request()->routeIs('manajer.hpp-margin*') ? 'active' : '' }}" href="{{ route('manajer.hpp-margin') }}"><span class="nav-icon">&#9636;</span>HPP &amp; Margin</a>
                    <a class="nav-link {{ request()->routeIs('manajer.laporan') ? 'active' : '' }}" href="{{ route('manajer.laporan') }}"><span class="nav-icon">&#9641;</span>Laporan</a>
                </nav>
            @endif

            <div class="sidebar-bottom">
                <div class="staff-label">Masuk sebagai</div>
                <div class="staff-name">{{ auth()->user()->nama }}</div>
                <div class="staff-role">{{ auth()->user()->role }}</div>
                <a class="staff-password" href="{{ route('password.change') }}">Ganti password</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="logout-button" type="submit">Keluar</button>
                </form>
            </div>
        </aside>

        <main class="main-content">
            <header class="page-head">
                <div>
                    <div class="eyebrow">@yield('eyebrow', auth()->user()->role)</div>
                    <h1>@yield('heading', 'Kedai Kopi Senja')</h1>
                    @hasSection('description')
                        <p class="page-description">@yield('description')</p>
                    @endif
                </div>
                <div class="page-head-actions">@yield('head-actions')</div>
            </header>

            @if (session('success'))
                <div class="alert alert-success" role="status">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="alert alert-error" role="alert">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-error" role="alert">
                    <strong>Periksa kembali data berikut:</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
    @stack('scripts')
</body>
</html>
