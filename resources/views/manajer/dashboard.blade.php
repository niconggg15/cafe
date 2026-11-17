@extends('layouts.app')

@section('title', 'Dashboard Manajer')
@section('heading', 'Dashboard')
@section('description', 'Ringkasan kondisi menu, bahan, dan transaksi hari ini.')

@section('content')
    <div class="dashboard-grid">
        <section class="panel dashboard-card">
            <div class="dashboard-label">Penjualan hari ini</div>
            <strong class="dashboard-value">Rp{{ number_format($penjualanHariIni, 0, ',', '.') }}</strong>
            <p class="dashboard-caption">Dari pesanan yang tidak dibatalkan</p>
        </section>
        <section class="panel dashboard-card">
            <div class="dashboard-label">Jumlah pesanan hari ini</div>
            <strong class="dashboard-value">{{ $jumlahPesananHariIni }}</strong>
            <p class="dashboard-caption">Pesanan yang tidak dibatalkan</p>
        </section>
        <section class="panel dashboard-card">
            <div class="dashboard-label">Menu aktif</div>
            <strong class="dashboard-value">{{ $menuAktif }}</strong>
            <p class="dashboard-caption">{{ $menuAktif }} menu siap dijual</p>
        </section>
        <section class="panel dashboard-card">
            <div class="dashboard-label">Bahan menipis</div>
            <strong class="dashboard-value">{{ $bahanMenipis->count() }}</strong>
            <p class="dashboard-caption">Pada atau di bawah batas minimum</p>
        </section>
    </div>
    @if ($bahanMenipis->isNotEmpty())
        <section class="panel section-gap">
            <div class="panel-head">
                <div>
                    <h2>Bahan menipis</h2>
                    <p class="panel-hint">Bahan yang perlu segera diperiksa atau ditambah stoknya.</p>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Bahan</th><th>Stok saat ini</th><th>Batas minimum</th></tr></thead>
                    <tbody>
                    @foreach ($bahanMenipis as $bahan)
                        <tr>
                            <td class="table-primary">{{ $bahan->nama_bahan }}</td>
                            <td>{{ number_format($bahan->stok, 3, ',', '.') }} {{ $bahan->satuan }}</td>
                            <td>{{ number_format($bahan->batas_minimum, 3, ',', '.') }} {{ $bahan->satuan }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
    <section class="panel section-gap">
        <div class="panel-head">
            <div>
                <h2>Penjualan per menu</h2>
                <p class="panel-hint">Porsi terjual per hari selama 7 hari terakhir. Tren diperbarui saat Dashboard dibuka.</p>
            </div>
        </div>
        @if ($trenMenu->isEmpty())
            <p class="empty-state">Belum ada penjualan dalam 7 hari terakhir.</p>
        @else
            <div class="dashboard-chart-wrap">
                <svg class="dashboard-chart" viewBox="0 0 640 210" role="img" aria-label="Grafik porsi terjual per menu selama 7 hari terakhir">
                    @foreach ([30, 75, 120, 165] as $y)
                        <line class="dashboard-chart-gridline" x1="50" y1="{{ $y }}" x2="590" y2="{{ $y }}"></line>
                    @endforeach
                    @foreach ($tanggalTren as $index => $tanggal)
                        <text class="dashboard-chart-label" x="{{ 50 + $index * 90 }}" y="195" text-anchor="middle">{{ $tanggal->format('d/m') }}</text>
                    @endforeach
                    @foreach ($trenMenu as $series)
                        <polyline class="dashboard-series-line dashboard-series-{{ $loop->index }}" points="{{ $series['points'] }}"></polyline>
                    @endforeach
                </svg>
                <div class="dashboard-legend" aria-label="Legenda menu">
                    @foreach ($trenMenu as $series)
                        <span class="dashboard-legend-item">
                            <span class="dashboard-legend-mark dashboard-series-{{ $loop->index }}" aria-hidden="true"></span>
                            {{ $series['nama_menu'] }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif
    </section>
@endsection
