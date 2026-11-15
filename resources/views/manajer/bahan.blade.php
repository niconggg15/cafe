@extends('layouts.app')

@section('title', 'Kelola Bahan')
@section('heading', 'Kelola Bahan')
@section('description', 'Catat bahan, satuan dasar, stok, dan harga pokok bahan.')

@section('content')
    <div class="manager-workspace">
        <section class="panel">
            <div class="panel-head"><div><h2>Daftar bahan</h2><p class="panel-hint">Stok dan harga per satuan bahan.</p></div></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Bahan</th><th>Satuan</th><th>Stok</th><th>Harga/satuan</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse ($bahans as $bahan)
                        <tr>
                            <td class="table-primary">{{ $bahan->nama_bahan }}</td>
                            <td>{{ $bahan->satuan }}</td>
                            <td>{{ number_format($bahan->stok, 3, ',', '.') }}</td>
                            <td>Rp{{ number_format($bahan->harga_per_satuan, 0, ',', '.') }}</td>
                            <td>
                                <details class="edit-details">
                                    <summary class="button button-small button-secondary">Ubah</summary>
                                    <form class="stack-form" method="POST" action="{{ route('manajer.bahan.update', $bahan) }}">
                                        @csrf @method('PUT')
                                        <label>Nama bahan<input name="nama_bahan" value="{{ $bahan->nama_bahan }}" required></label>
                                        <label>Satuan<select name="satuan">@foreach (['g', 'ml', 'pcs'] as $satuan)<option @selected($bahan->satuan === $satuan)>{{ $satuan }}</option>@endforeach</select></label>
                                        <label>Batas minimum<input type="number" name="batas_minimum" value="{{ $bahan->batas_minimum }}" min="0" step="0.001" required></label>
                                        <label>Harga/satuan<input type="number" name="harga_per_satuan" value="{{ $bahan->harga_per_satuan }}" min="0" step="0.01" required></label>
                                        <button class="button button-primary button-small" type="submit">Simpan</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">Belum ada bahan.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($bahans->hasPages())<div class="pagination">{{ $bahans->links() }}</div>@endif
        </section>
        <section class="panel manager-form">
            <h2>Tambah bahan</h2>
            <p class="panel-hint">Stok awal dicatat melalui Stok Masuk.</p>
            <form method="POST" action="{{ route('manajer.bahan.simpan') }}" class="stack-form">
                @csrf
                <label>Nama bahan<input name="nama_bahan" value="{{ old('nama_bahan') }}" maxlength="255" required></label>
                <label>Satuan dasar<select name="satuan" required><option value="g">g</option><option value="ml">ml</option><option value="pcs">pcs</option></select></label>
                <label>Batas minimum<input type="number" name="batas_minimum" value="0" min="0" step="0.001" required></label>
                <label>Harga per satuan<input type="number" name="harga_per_satuan" value="0" min="0" step="0.01" required></label>
                <button class="button button-primary" type="submit">Simpan</button>
            </form>
        </section>
    </div>
@endsection
