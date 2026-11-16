@extends('layouts.app')

@section('title', 'Kelola Menu & Resep')
@section('heading', 'Kelola Menu & Resep')
@section('description', 'Atur menu dan susun formula resep dari bahan baku.')

@section('content')
    <div class="manager-workspace manager-menu-recipe-workspace">
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
                                    <a class="button button-small button-secondary" href="{{ route('manajer.menu', ['menu_id' => $menu->id]) }}#recipe-builder">Atur resep</a>
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
        <div class="manager-menu-editor">
            <section class="panel manager-form">
                <h2>Tambah menu</h2>
                <p class="panel-hint">Setelah membuat menu, lengkapi formulanya di bagian Resep.</p>
                <form method="POST" action="{{ route('manajer.menu.simpan') }}" class="stack-form">
                    @csrf
                    <label>Nama menu<input name="nama_menu" value="{{ old('nama_menu') }}" maxlength="255" required></label>
                    <label>Kategori<select name="kategori" required>@foreach (['Kopi', 'Non-kopi', 'Makanan'] as $kategori)<option>{{ $kategori }}</option>@endforeach</select></label>
                    <label>Harga jual (Rp)<input type="number" name="harga_jual" min="0" step="1" required></label>
                    <button class="button button-primary" type="submit">Simpan</button>
                </form>
            </section>

            <section class="panel form-panel manager-recipe-panel" id="recipe-builder">
                <div class="panel-head panel-head-inline">
                    <div><h2>Formula resep</h2><p class="panel-hint">Pilih menu, lalu tambahkan bahan dan takarannya.</p></div>
                    <form method="GET" action="{{ route('manajer.menu') }}" class="inline-form">
                        <label for="recipe-menu">Pilih menu</label>
                        <select id="recipe-menu" name="menu_id" onchange="this.form.submit()" required>
                            @foreach ($recipeMenus as $recipeMenu)
                                <option value="{{ $recipeMenu->id }}" @selected($selectedMenu?->id === $recipeMenu->id)>{{ $recipeMenu->nama_menu }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @if (! $selectedMenu)
                    <p class="empty-state">Tambahkan menu dan bahan sebelum menyusun resep.</p>
                @elseif ($bahans->isEmpty())
                    <p class="empty-state">Belum ada bahan. <a href="{{ route('manajer.bahan') }}">Tambah bahan</a> sebelum membuat resep.</p>
                @elseif ($selectedMenu->resep->isEmpty() && $bahansAktif->isEmpty())
                    <p class="empty-state">Tidak ada bahan aktif. Aktifkan bahan di <a href="{{ route('manajer.bahan') }}">Kelola Bahan</a> untuk membuat resep.</p>
                @else
                    @php($resepForm = old('bahan', $selectedMenu->resep->mapWithKeys(fn ($resep) => [$resep->bahan_id => $resep->takaran])->all()))
                    @if ($selectedMenu->resep->isEmpty())
                        <p class="system-note" id="recipe-empty-state">Resep belum dibuat untuk menu ini. Tambahkan minimal satu bahan dan takaran untuk membuat formulanya.</p>
                    @endif
                    <form method="POST" action="{{ route('manajer.resep.simpan') }}" id="recipe-form">
                        @csrf
                        <input type="hidden" name="menu_id" value="{{ $selectedMenu->id }}">
                        <div class="table-wrap">
                            <table class="recipe-table">
                                <thead><tr><th>Bahan</th><th>Takaran</th><th>Satuan dasar</th><th>Aksi</th></tr></thead>
                                <tbody id="recipe-rows">
                                @foreach ($bahans as $bahan)
                                    @php($takaran = $resepForm[$bahan->id] ?? null)
                                    @if ($takaran !== null && $takaran !== '')
                                        <tr data-recipe-row data-bahan-id="{{ $bahan->id }}">
                                            <td>{{ $bahan->nama_bahan }}</td>
                                            <td><input class="table-input" type="number" name="bahan[{{ $bahan->id }}]" value="{{ $takaran }}" min="0" step="0.001" required></td>
                                            <td>{{ $bahan->satuan }}</td>
                                            <td><button class="button button-small button-secondary" type="button" data-remove-recipe>Hapus</button></td>
                                        </tr>
                                    @endif
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @if ($bahansAktif->isNotEmpty())
                            <div class="recipe-add-row">
                                <label>Tambah bahan ke resep
                                    <select id="recipe-material" required>
                                        <option value="">Pilih bahan</option>
                                        @foreach ($bahansAktif as $bahan)
                                            <option value="{{ $bahan->id }}" data-name="{{ $bahan->nama_bahan }}" data-unit="{{ $bahan->satuan }}">{{ $bahan->nama_bahan }}</option>
                                        @endforeach
                                    </select>
                                </label>
                                <label>Takaran<input id="recipe-amount" type="number" min="0.001" step="0.001" placeholder="0" required></label>
                                <button class="button button-secondary" type="button" id="add-recipe-material">Tambah</button>
                            </div>
                        @else
                            <p class="empty-state">Semua bahan yang bisa ditambahkan berstatus nonaktif. Aktifkan bahan untuk menambahkannya.</p>
                        @endif
                        <button class="button button-primary" type="submit">Simpan Resep</button>
                    </form>
                @endif
            </section>
        </div>
    </div>
@endsection
