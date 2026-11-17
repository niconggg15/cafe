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
                        @php($satuanTerkunci = (float) $bahan->stok > 0 || $bahan->resep_count > 0 || $bahan->stok_masuk_count > 0)
                        <tr>
                            <td class="table-primary">{{ $bahan->nama_bahan }}</td>
                            <td>{{ $bahan->satuan }}</td>
                            <td>{{ number_format($bahan->stok, 3, ',', '.') }}</td>
                            <td>Rp{{ number_format($bahan->harga_per_satuan, 0, ',', '.') }}</td>
                            <td><span class="badge badge-{{ $bahan->status === 'Aktif' ? 'green' : 'muted' }}">{{ $bahan->status }}</span></td>
                            <td>
                                <div class="table-actions">
                                    <details class="edit-details">
                                        <summary class="button button-small button-secondary">Ubah</summary>
                                        <form class="stack-form" method="POST" action="{{ route('manajer.bahan.update', $bahan) }}">
                                            @csrf @method('PUT')
                                            <label>Nama bahan<input name="nama_bahan" value="{{ $bahan->nama_bahan }}" maxlength="255" required></label>
                                            <label>Satuan dasar
                                                @if ($satuanTerkunci)
                                                    <input value="{{ $bahan->satuan }}" disabled>
                                                    <input type="hidden" name="satuan" value="{{ $bahan->satuan }}">
                                                    <small>Satuan terkunci karena bahan sudah dipakai.</small>
                                                @else
                                                    <select name="satuan" required>
                                                        @foreach (['g', 'ml', 'pcs'] as $satuan)
                                                            <option value="{{ $satuan }}" @selected($bahan->satuan === $satuan)>{{ $satuan }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </label>
                                            <label>Batas minimum<input type="number" name="batas_minimum" value="{{ $bahan->batas_minimum }}" min="0" step="0.001" required></label>
                                            <label>Harga per satuan<input type="number" name="harga_per_satuan" value="{{ $bahan->harga_per_satuan }}" min="0" step="0.01" required></label>
                                            <button class="button button-primary button-small" type="submit">Simpan</button>
                                        </form>
                                    </details>
                                    <form method="POST" action="{{ route('manajer.bahan.status', $bahan) }}">
                                        @csrf @method('PATCH')
                                        <button class="button button-small {{ $bahan->status === 'Aktif' ? 'button-secondary' : 'button-primary' }}" type="submit">
                                            {{ $bahan->status === 'Aktif' ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                    @if ((int) $bahan->resep_count === 0 && (int) $bahan->stok_masuk_count === 0 && (float) $bahan->stok <= 0)
                                        <form method="POST" action="{{ route('manajer.bahan.hapus', $bahan) }}" onsubmit="return confirm('Hapus bahan ini?')">
                                            @csrf @method('DELETE')
                                            <button class="button button-small button-danger" type="submit">Hapus</button>
                                        </form>
                                    @endif
                                </div>
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
