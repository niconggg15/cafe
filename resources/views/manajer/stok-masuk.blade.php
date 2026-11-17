@extends('layouts.app')

@section('title', 'Stok Masuk')
@section('heading', 'Catat Stok Masuk')
@section('description', 'Pembelian bahan menambah stok. Jumlah harus lebih dari 0.')

@section('content')
    <div class="manager-workspace manager-workspace-wide">
        <section class="panel manager-form">
            <h2>Stok masuk baru</h2>
            <p class="panel-hint">Jumlah harus lebih dari 0.</p>
            @if ($bahans->isEmpty())
                <p class="empty-state">Tidak ada bahan aktif. Aktifkan bahan sebelum mencatat stok masuk.</p>
            @else
                <form method="POST" action="{{ route('manajer.stok-masuk.simpan') }}" class="stack-form">
                    @csrf
                    <label>Tanggal<input type="date" name="tanggal" value="{{ now()->toDateString() }}" required></label>
                    <label>Bahan<select name="bahan_id" required>@foreach ($bahans as $bahan)<option value="{{ $bahan->id }}">{{ $bahan->nama_bahan }}</option>@endforeach</select></label>
                    <label>Jumlah (satuan bahan)<input type="number" name="jumlah" min="0.001" step="0.001" required></label>
                    <button class="button button-primary" type="submit">Simpan Stok Masuk</button>
                </form>
            @endif
        </section>
        <section class="panel">
            <div class="panel-head"><div><h2>Riwayat stok masuk</h2><p class="panel-hint">Penerimaan bahan tercatat oleh Manajer.</p></div></div>
            <div class="table-wrap"><table>
                <thead><tr><th>Tanggal</th><th>Bahan</th><th>Jumlah</th><th>Dicatat oleh</th></tr></thead>
                <tbody>
                @forelse ($riwayat as $item)
                    <tr><td>{{ \Carbon\Carbon::parse($item->tanggal)->format('d-m-Y') }}</td><td>{{ $item->bahan->nama_bahan }}</td><td>{{ rtrim(rtrim(number_format($item->jumlah, 3, ',', '.'), '0'), ',') }} {{ $item->bahan->satuan }}</td><td>{{ $item->user->nama }}</td></tr>
                @empty
                    <tr><td colspan="4" class="empty-state">Belum ada catatan stok masuk.</td></tr>
                @endforelse
                </tbody>
            </table></div>
            @if ($riwayat->hasPages())<div class="pagination">{{ $riwayat->links() }}</div>@endif
        </section>
    </div>
@endsection
