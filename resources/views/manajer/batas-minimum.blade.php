@extends('layouts.app')

@section('title', 'Batas Minimum')
@section('heading', 'Batas Minimum & Bahan Menipis')
@section('description', 'Daftar menipis dihitung dari stok terkini setiap kali halaman dibuka.')

@section('content')
    <section class="panel">
        <div class="panel-head"><div><h2>Bahan menipis</h2><p class="panel-hint">Stok sama dengan atau di bawah batas minimum.</p></div><span class="count-badge">{{ $menipis->count() }}</span></div>
        <div class="table-wrap"><table>
            <thead><tr><th>Bahan</th><th>Stok</th><th>Harga/satuan</th><th>Ubah batas min.</th></tr></thead>
            <tbody>
            @forelse ($bahans as $bahan)
                <tr>
                    <td class="table-primary">{{ $bahan->nama_bahan }} @if ($bahan->stok <= $bahan->batas_minimum)<span class="badge badge-amber">Menipis</span>@endif</td>
                    <td>{{ number_format($bahan->stok, 3, ',', '.') }} {{ $bahan->satuan }}</td>
                    <td>Rp{{ number_format($bahan->harga_per_satuan, 0, ',', '.') }}</td>
                    <td>
                        <form class="inline-form" method="POST" action="{{ route('manajer.batas-minimum.update', $bahan) }}">
                            @csrf @method('PUT')
                            <input class="table-input" type="number" name="batas_minimum" value="{{ $bahan->batas_minimum }}" min="0" step="0.001" required>
                            <button class="button button-small button-secondary" type="submit">Simpan</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="empty-state">Belum ada bahan.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </section>
@endsection
