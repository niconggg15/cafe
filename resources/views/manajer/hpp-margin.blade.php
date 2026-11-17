@extends('layouts.app')

@section('title', 'HPP & Margin')
@section('heading', 'HPP dan Margin per Menu')
@section('description', 'HPP = jumlah (takaran x harga bahan per satuan dasar). Margin = harga jual - HPP')

@section('head-actions')
    <form method="POST" action="{{ route('manajer.hpp-margin.sinkron') }}">
        @csrf
        <button class="button button-primary" type="submit">Sinkron harga supplier</button>
    </form>
@endsection

@section('content')
    <section class="panel">
        <div class="table-wrap"><table>
            <thead><tr><th>Menu</th><th>Harga jual</th><th>HPP</th><th>Margin</th><th>Margin %</th></tr></thead>
            <tbody>
            @forelse ($menus as $menu)
                <tr>
                    <td class="table-primary">{{ $menu->nama_menu }}</td>
                    <td>Rp{{ number_format($menu->harga_jual, 0, ',', '.') }}</td>
                    <td>Rp{{ number_format($menu->hpp, 0, ',', '.') }}</td>
                    <td class="{{ $menu->margin < 0 ? 'text-danger' : '' }}">Rp{{ number_format($menu->margin, 0, ',', '.') }}</td>
                    <td>{{ number_format($menu->margin_percent, 0) }}%</td>
                </tr>
            @empty
                <tr><td colspan="5" class="empty-state">Belum ada menu.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
    <p class="system-note">Harga per {{ $hargaTerbaru ? \Carbon\Carbon::parse($hargaTerbaru)->format('d M Y') : 'belum tersedia' }} (jika layanan Supplier tidak terhubung, harga terakhir tersimpan tetap digunakan).</p>
@endsection
