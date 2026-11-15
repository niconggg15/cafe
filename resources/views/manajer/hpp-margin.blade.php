@extends('layouts.app')

@section('title', 'HPP & Margin')
@section('heading', 'HPP & Margin')
@section('description', 'HPP dihitung dari resep dan harga bahan. Margin adalah harga jual dikurangi HPP.')

@section('head-actions')
    <form method="POST" action="{{ route('manajer.hpp-margin.sinkron') }}">
        @csrf
        <button class="button button-secondary" type="submit">Sinkronkan Supplier</button>
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
                    <td>
                        <form class="inline-form" method="POST" action="{{ route('manajer.hpp-margin.update', $menu) }}">
                            @csrf @method('PUT')
                            <input class="table-input" type="number" name="harga_jual" value="{{ $menu->harga_jual }}" min="0" step="1" required>
                            <button class="button button-small button-secondary" type="submit">Simpan</button>
                        </form>
                    </td>
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
    <p class="system-note">Harga bahan diperbarui: {{ $hargaTerbaru ? \Carbon\Carbon::parse($hargaTerbaru)->format('d/m/Y') : 'belum tersedia' }}. Bila Supplier tidak dapat dihubungi, harga terakhir tetap digunakan.</p>
@endsection
