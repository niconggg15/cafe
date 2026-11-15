@extends('layouts.app')

@section('title', 'Laporan')
@section('heading', 'Laporan')
@section('description', 'Ringkasan penjualan, bahan menipis, dan margin menu.')

@section('content')
    <section class="panel">
        <nav class="report-tabs" aria-label="Jenis laporan">
            <a class="report-tab {{ $tab === 'penjualan' ? 'active' : '' }}" href="{{ route('manajer.laporan', ['tab' => 'penjualan']) }}">Penjualan per Menu</a>
            <a class="report-tab {{ $tab === 'bahan-menipis' ? 'active' : '' }}" href="{{ route('manajer.laporan', ['tab' => 'bahan-menipis']) }}">Bahan Menipis</a>
            <a class="report-tab {{ $tab === 'margin' ? 'active' : '' }}" href="{{ route('manajer.laporan', ['tab' => 'margin']) }}">Margin per Menu</a>
        </nav>

        @if ($tab === 'penjualan')
            <form method="GET" action="{{ route('manajer.laporan') }}" class="report-period">
                <input type="hidden" name="tab" value="penjualan">
                <label>Periode awal<input type="date" name="mulai" value="{{ $mulai }}"></label>
                <span>s/d</span>
                <label>Periode akhir<input type="date" name="sampai" value="{{ $sampai }}"></label>
                <button class="button button-primary" type="submit">Tampilkan</button>
            </form>
            <div class="table-wrap"><table>
                <thead><tr><th>Menu</th><th>Porsi terjual</th><th>Pendapatan</th></tr></thead>
                <tbody>
                @forelse ($penjualan as $item)
                    <tr><td class="table-primary">{{ $item->nama_menu }}</td><td>{{ $item->jumlah_terjual }}</td><td>Rp{{ number_format($item->total_penjualan, 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td colspan="3" class="empty-state">Tidak ada penjualan pada rentang tanggal ini.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        @elseif ($tab === 'bahan-menipis')
            <div class="table-wrap"><table>
                <thead><tr><th>Bahan</th><th>Satuan</th><th>Stok</th><th>Batas minimum</th></tr></thead>
                <tbody>
                @forelse ($menipis as $bahan)
                    <tr><td class="table-primary">{{ $bahan->nama_bahan }}</td><td>{{ $bahan->satuan }}</td><td>{{ number_format($bahan->stok, 3, ',', '.') }}</td><td>{{ number_format($bahan->batas_minimum, 3, ',', '.') }}</td></tr>
                @empty
                    <tr><td colspan="4" class="empty-state">Tidak ada bahan menipis.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        @else
            <div class="table-wrap"><table>
                <thead><tr><th>Menu</th><th>Harga jual</th><th>HPP</th><th>Margin</th></tr></thead>
                <tbody>
                @forelse ($menus as $menu)
                    <tr><td class="table-primary">{{ $menu->nama_menu }}</td><td>Rp{{ number_format($menu->harga_jual, 0, ',', '.') }}</td><td>Rp{{ number_format($menu->hpp, 0, ',', '.') }}</td><td>Rp{{ number_format($menu->margin, 0, ',', '.') }}</td></tr>
                @empty
                    <tr><td colspan="4" class="empty-state">Belum ada menu.</td></tr>
                @endforelse
                </tbody>
            </table></div>
        @endif
    </section>
@endsection
