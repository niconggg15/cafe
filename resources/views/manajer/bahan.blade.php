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
                    <thead><tr><th>Bahan</th><th>Satuan</th><th>Stok</th><th>Harga/satuan</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse ($bahans as $bahan)
                        <tr>
                            <td class="table-primary">{{ $bahan->nama_bahan }}</td>
                            <td>{{ $bahan->satuan }}</td>
                            <td>{{ number_format($bahan->stok, 3, ',', '.') }}</td>
                            <td>Rp{{ number_format($bahan->harga_per_satuan, 0, ',', '.') }}</td>
                            <td><span class="badge badge-{{ $bahan->status === 'Aktif' ? 'green' : 'muted' }}">{{ $bahan->status }}</span></td>
                            <td>
                                <form method="POST" action="{{ route('manajer.bahan.status', $bahan) }}">
                                    @csrf @method('PATCH')
                                    <button class="button button-small {{ $bahan->status === 'Aktif' ? 'button-secondary' : 'button-primary' }}" type="submit">
                                        {{ $bahan->status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">Belum ada bahan.</td></tr>
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
