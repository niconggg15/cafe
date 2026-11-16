@extends('layouts.app')

@section('title', 'Pesanan Baru')
@section('heading', 'Pesanan Baru')
@section('eyebrow', 'Transaksi')
@section('description', 'Pilih menu, atur jumlah, lalu lengkapi rincian pesanan.')

@section('content')
    <div class="order-workspace">
        <section class="panel" aria-labelledby="menu-heading">
            <div class="panel-head">
                <div>
                    <h2 id="menu-heading">Menu</h2>
                    <p class="panel-hint">Daftar harga dan status menu terkini.</p>
                </div>
                <label class="search-wrap"><span class="sr-only">Cari menu</span><input id="menu-search" type="search" placeholder="Cari menu..."></label>
            </div>
            <div class="category-filters" role="group" aria-label="Filter kategori">
                @foreach (['Semua', 'Kopi', 'Non-kopi', 'Makanan'] as $kategori)
                    <button class="category-filter {{ $loop->first ? 'active' : '' }}" type="button" data-category-filter="{{ $kategori }}">{{ $kategori }}</button>
                @endforeach
            </div>
            <div class="table-wrap">
                <table>
                    <thead><tr><th>Menu</th><th>Kategori</th><th>Harga</th><th>Status</th><th>Jumlah</th></tr></thead>
                    <tbody>
                    @forelse ($menus as $menu)
                        <tr data-menu-row data-name="{{ mb_strtolower($menu->nama_menu) }}" data-row-category="{{ $menu->kategori }}">
                            <td><span class="table-primary">{{ $menu->nama_menu }}</span></td>
                            <td>{{ $menu->kategori }}</td>
                            <td>Rp{{ number_format($menu->harga_jual, 0, ',', '.') }}</td>
                            <td><span class="badge badge-{{ $menu->status === 'Aktif' && $menu->resep->isNotEmpty() ? 'green' : 'muted' }}">{{ $menu->status === 'Aktif' && $menu->resep->isEmpty() ? 'Belum ada resep' : $menu->status }}</span></td>
                            <td class="menu-quantity">
                                @if ($menu->status === 'Aktif' && $menu->resep->isNotEmpty())
                                    <button class="icon-button" type="button" data-remove-menu="{{ $menu->id }}" aria-label="Kurangi {{ $menu->nama_menu }}">−</button>
                                    <span class="menu-quantity-value" data-menu-count="{{ $menu->id }}">0</span>
                                    <button class="icon-button" type="button" data-add-menu="{{ $menu->id }}" data-name="{{ $menu->nama_menu }}" data-category="{{ $menu->kategori }}" data-price="{{ $menu->harga_jual }}" aria-label="Tambah {{ $menu->nama_menu }}">+</button>
                                @else
                                    <span class="menu-quantity-value">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="empty-state">Belum ada menu yang tercatat.</td></tr>
                    @endforelse
                    </tbody>
                </table>
                <div class="empty-state" id="menu-search-empty" hidden>Menu tidak ditemukan.</div>
            </div>
        </section>

        <section class="panel order-panel" aria-labelledby="order-heading">
            <div class="panel-head">
                <div><h2 id="order-heading">Rincian Pesanan</h2><p class="panel-hint">Periksa kembali sebelum menyimpan.</p></div>
                <span class="count-badge" id="item-count">0</span>
            </div>
            <form method="POST" action="{{ route('kasir.pesanan.simpan') }}" id="order-form" data-initial-cart="{{ json_encode(old('items', [])) }}">
                @csrf
                <div class="order-body">
                    <div id="order-items" aria-live="polite"></div>
                    <div class="order-empty" id="order-empty">Belum ada menu dipilih.</div>
                    <div id="cart-inputs"></div>
                    <div class="form-grid">
                        <label>Nomor meja
                            <select name="no_meja" required>
                                <option value="">Pilih meja</option>
                                @for ($meja = 1; $meja <= 10; $meja++)
                                    <option value="{{ $meja }}" @selected(old('no_meja') == $meja)>{{ $meja }}</option>
                                @endfor
                            </select>
                        </label>
                        <fieldset class="payment-methods">
                            <legend>Metode bayar</legend>
                            <div class="payment-method-options">
                                <label><input type="radio" name="metode_bayar" value="Tunai" @checked(old('metode_bayar', 'Tunai') === 'Tunai') required> Tunai</label>
                                <label><input type="radio" name="metode_bayar" value="QRIS" @checked(old('metode_bayar') === 'QRIS') required> QRIS</label>
                            </div>
                        </fieldset>
                    </div>
                    <div class="totals">
                        <div><span>Subtotal</span><strong id="subtotal">Rp0</strong></div>
                        <div><span>Biaya layanan (5%)</span><strong id="service-charge">Rp0</strong></div>
                        <div><span>Pajak (10%)</span><strong id="tax">Rp0</strong></div>
                        <div class="grand-total"><span>Total bayar</span><strong id="grand-total">Rp0</strong></div>
                    </div>
                    <button class="button button-primary button-full" id="save-order" type="submit" disabled>Buat Pesanan</button>
                </div>
            </form>
        </section>
    </div>
@endsection
