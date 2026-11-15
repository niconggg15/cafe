@extends('layouts.app')

@section('title', 'Kelola Menu')
@section('heading', 'Kelola Menu')
@section('description', 'Atur nama, harga jual, dan ketersediaan menu.')

@section('content')
    <div class="manager-workspace">
        <section class="panel">
            <div class="panel-head"><div><h2>Daftar menu</h2><p class="panel-hint">Nonaktifkan menu tanpa menghapus riwayat transaksinya.</p></div></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Menu</th><th>Kategori</th><th>Harga jual</th><th>Status</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse ($menus as $menu)
                        <tr>
                            <td class="table-primary">{{ $menu->nama_menu }}</td>
                            <td>{{ $menu->kategori }}</td>
                            <td>Rp{{ number_format($menu->harga_jual, 0, ',', '.') }}</td>
                            <td><span class="badge badge-{{ $menu->status === 'Aktif' ? 'green' : 'muted' }}">{{ $menu->status }}</span></td>
                            <td>
                                <details class="edit-details">
                                    <summary class="button button-small button-secondary">Ubah</summary>
                                    <form class="stack-form" method="POST" action="{{ route('manajer.menu.update', $menu) }}">
                                        @csrf @method('PUT')
                                        <label>Nama menu<input name="nama_menu" value="{{ $menu->nama_menu }}" required></label>
                                        <label>Kategori<select name="kategori">@foreach (['Kopi', 'Non-kopi', 'Makanan'] as $kategori)<option @selected($menu->kategori === $kategori)>{{ $kategori }}</option>@endforeach</select></label>
                                        <label>Harga jual<input type="number" name="harga_jual" value="{{ $menu->harga_jual }}" min="0" step="1" required></label>
                                        <label>Status<select name="status">@foreach (['Aktif', 'Habis', 'Nonaktif'] as $status)<option @selected($menu->status === $status)>{{ $status }}</option>@endforeach</select></label>
                                        <button class="button button-primary button-small" type="submit">Simpan</button>
                                    </form>
                                </details>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">Belum ada menu.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($menus->hasPages())<div class="pagination">{{ $menus->links() }}</div>@endif
        </section>
        <section class="panel manager-form">
            <h2>Tambah menu</h2>
            <form method="POST" action="{{ route('manajer.menu.simpan') }}" class="stack-form">
                @csrf
                <label>Nama menu<input name="nama_menu" value="{{ old('nama_menu') }}" maxlength="255" required></label>
                <label>Kategori<select name="kategori" required>@foreach (['Kopi', 'Non-kopi', 'Makanan'] as $kategori)<option>{{ $kategori }}</option>@endforeach</select></label>
                <label>Harga jual (Rp)<input type="number" name="harga_jual" min="0" step="1" required></label>
                <button class="button button-primary" type="submit">Simpan</button>
            </form>
        </section>
    </div>
@endsection
