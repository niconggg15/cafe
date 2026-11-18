@extends('layouts.app')

@section('title', 'Kelola Menu')
@section('heading', 'Kelola Menu')
@section('description', 'Tambah menu, ubah harga, dan atur status ketersediaan.')

@section('content')
    <div class="manager-workspace manager-menu-workspace">
        <section class="panel">
            <div class="panel-head"><div><h2>Daftar menu</h2><p class="panel-hint">Nonaktifkan menu tanpa menghapus riwayat transaksinya.</p></div></div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Menu</th><th>Kategori</th><th>Harga jual</th><th>Status</th><th>Resep</th><th>Aksi</th></tr></thead>
                    <tbody>
                    @forelse ($menus as $menu)
                        <tr>
                            <td class="table-primary">{{ $menu->nama_menu }}</td>
                            <td>{{ $menu->kategori }}</td>
                            <td>Rp{{ number_format($menu->harga_jual, 0, ',', '.') }}</td>
                            <td><span class="badge badge-{{ $menu->status === 'Aktif' ? 'green' : 'muted' }}">{{ $menu->status }}</span></td>
                            <td>
                                @if ($menu->resep_count > 0)
                                    <span class="badge badge-green">{{ $menu->resep_count }} bahan</span>
                                @else
                                    <span class="badge badge-amber" data-recipe-status="{{ $menu->id }}">Belum ada resep</span>
                                @endif
                            </td>
                            <td>
                                <div class="table-actions">
                                    <a class="button button-small button-secondary" href="{{ route('manajer.resep', ['menu_id' => $menu->id]) }}">Kelola Resep</a>
                                    <a class="button button-small button-secondary" href="{{ route('manajer.menu', ['edit_menu_id' => $menu->id, 'page' => $menus->currentPage()]) }}#edit-menu">Ubah</a>
                                    <form method="POST" action="{{ route('manajer.menu.status', $menu) }}">
                                        @csrf @method('PATCH')
                                        <button class="button button-small {{ $menu->status === 'Nonaktif' ? 'button-primary' : 'button-secondary' }}" type="submit">
                                            {{ $menu->status === 'Nonaktif' ? 'Aktifkan' : 'Nonaktifkan' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty-state">Belum ada menu.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            @if ($menus->hasPages())<div class="pagination">{{ $menus->links() }}</div>@endif
        </section>
        <div class="manager-form-stack">
            <section class="panel manager-form" id="form-menu">
                <h2>Tambah menu</h2>
                <form method="POST" action="{{ route('manajer.menu.simpan') }}" class="stack-form">
                    @csrf
                    <label>Nama menu<input name="nama_menu" value="{{ $editMenu ? '' : old('nama_menu') }}" maxlength="255" required></label>
                    <label>Kategori<select name="kategori" required><option value="">Pilih kategori</option>@foreach ($kategoriMenus as $kategori)<option value="{{ $kategori }}" @selected(! $editMenu && old('kategori') === $kategori)>{{ $kategori }}</option>@endforeach</select></label>
                    <label>Harga jual (Rp)<input type="number" name="harga_jual" value="{{ $editMenu ? '' : old('harga_jual') }}" min="0" step="1" required></label>
                    <button class="button button-primary" type="submit">Simpan</button>
                </form>
            </section>
            @if ($editMenu)
                <section class="panel manager-form" id="edit-menu">
                    <h2>Ubah menu: {{ $editMenu->nama_menu }}</h2>
                    <form method="POST" action="{{ route('manajer.menu.update', $editMenu) }}" class="stack-form">
                        @csrf @method('PUT')
                        <label>Nama menu<input name="nama_menu" value="{{ old('nama_menu', $editMenu->nama_menu) }}" maxlength="255" required></label>
                        <label>Kategori<select name="kategori" required>@foreach ($kategoriMenus as $kategori)<option value="{{ $kategori }}" @selected(old('kategori', $editMenu->kategori) === $kategori)>{{ $kategori }}</option>@endforeach</select></label>
                        <label>Harga jual (Rp)<input type="number" name="harga_jual" value="{{ old('harga_jual', $editMenu->harga_jual) }}" min="0" step="1" required></label>
                        <div class="table-actions">
                            <button class="button button-primary" type="submit">Simpan Perubahan</button>
                            <a class="button button-secondary" href="{{ route('manajer.menu') }}#form-menu">Batal</a>
                        </div>
                    </form>
                </section>
            @endif
        </div>
    </div>
@endsection
