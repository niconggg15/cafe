@extends('layouts.app')

@section('title', 'Struk Pesanan')
@section('heading')
    Struk Pesanan #{{ str_pad($pesanan->id, 4, '0', STR_PAD_LEFT) }}
@endsection
@section('description', 'Rincian transaksi Kedai Kopi Senja.')
@section('head-actions')
    <button class="button button-secondary" type="button" onclick="window.print()">Cetak struk</button>
@endsection

@section('content')
    <div class="receipt-workspace">
        <section class="panel receipt">
            <div class="receipt-head">
                <strong>Kedai Kopi Senja</strong>
                <span>STRUK PESANAN</span>
                <span>No. pesanan: #{{ str_pad($pesanan->id, 4, '0', STR_PAD_LEFT) }}</span>
                <span>{{ $pesanan->created_at->format('d/m/Y H:i') }}</span>
                <span>Meja {{ $pesanan->no_meja }} &middot; {{ $pesanan->metode_bayar }} &middot; {{ $pesanan->status }}</span>
            </div>
            <div class="receipt-lines">
                @foreach ($pesanan->detail as $detail)
                    <div><span>{{ $detail->jumlah }} &times; {{ $detail->nama_menu }}</span><strong>Rp{{ number_format($detail->harga_saat_transaksi * $detail->jumlah, 0, ',', '.') }}</strong></div>
                @endforeach
            </div>
            <div class="totals">
                <div><span>Subtotal</span><strong>Rp{{ number_format($pesanan->subtotal, 0, ',', '.') }}</strong></div>
                <div><span>Biaya layanan (5%)</span><strong>Rp{{ number_format($pesanan->service_charge, 0, ',', '.') }}</strong></div>
                <div><span>Pajak (10%)</span><strong>Rp{{ number_format($pesanan->pajak, 0, ',', '.') }}</strong></div>
                <div class="grand-total"><span>TOTAL</span><strong>Rp{{ number_format($pesanan->total_bayar, 0, ',', '.') }}</strong></div>
            </div>
            <p class="receipt-foot">Harga pada struk tidak berubah walau harga menu berubah.</p>
        </section>
        <div class="receipt-actions">
            <a class="button button-secondary" href="{{ route('kasir.pesanan.index') }}">Kembali ke Daftar Pesanan</a>
            <a class="button button-primary" href="{{ route('kasir.pesanan.baru') }}">Buat Pesanan Baru</a>
        </div>
    </div>
@endsection
