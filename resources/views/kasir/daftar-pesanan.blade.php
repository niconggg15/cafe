@extends('layouts.app')

@section('title', 'Daftar Pesanan')
@section('heading', 'Daftar Pesanan')
@section('description', 'Pantau dan perbarui pesanan yang sedang berjalan.')

@section('content')
    <section class="panel">
        <div class="panel-head"><div><h2>Daftar pesanan</h2><p class="panel-hint">Ubah status pesanan Baru, Diproses, Selesai.</p></div></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>No.</th><th>Meja</th><th>Item</th><th>Total</th><th>Status</th><th>Aksi</th></tr></thead>
                <tbody>
                @forelse ($pesanans as $pesanan)
                    <tr>
                        <td>#{{ str_pad($pesanan->id, 4, '0', STR_PAD_LEFT) }}</td>
                        <td>{{ $pesanan->no_meja }}</td>
                        <td>{{ $pesanan->detail->map(fn ($item) => $item->jumlah.' '.$item->nama_menu)->join(', ') }}</td>
                        <td>Rp{{ number_format($pesanan->total_bayar, 0, ',', '.') }}</td>
                        <td><span class="badge badge-{{ $pesanan->status === 'Selesai' ? 'green' : ($pesanan->status === 'Dibatalkan' ? 'red' : 'amber') }}">{{ $pesanan->status }}</span></td>
                        <td class="table-actions">
                            @if ($pesanan->status === 'Baru')
                                <form method="POST" action="{{ route('kasir.pesanan.status', $pesanan) }}">@csrf @method('PUT')<input type="hidden" name="status" value="Diproses"><button class="button button-small button-secondary" type="submit">Proses</button></form>
                                <form method="POST" action="{{ route('kasir.pesanan.batal', $pesanan) }}">@csrf<button class="button button-small button-secondary" type="submit">Batalkan</button></form>
                            @elseif ($pesanan->status === 'Diproses')
                                <form method="POST" action="{{ route('kasir.pesanan.status', $pesanan) }}">@csrf @method('PUT')<input type="hidden" name="status" value="Selesai"><button class="button button-small button-secondary" type="submit">Selesai</button></form>
                            @endif
                            <a class="button button-small button-secondary" href="{{ route('kasir.struk', $pesanan) }}">Struk</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="empty-state">Belum ada pesanan.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($pesanans->hasPages())<div class="pagination">{{ $pesanans->links() }}</div>@endif
    </section>
@endsection
