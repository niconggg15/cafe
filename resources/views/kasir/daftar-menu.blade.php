@extends('layouts.app')

@section('title', 'Daftar Menu')
@section('heading', 'Daftar Menu')
@section('description', 'Informasi harga dan ketersediaan menu.')

@section('content')
    <section class="panel">
        <div class="table-wrap">
            <table>
                <thead><tr><th>Menu</th><th>Kategori</th><th>Harga</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($menus as $menu)
                    <tr><td class="table-primary">{{ $menu->nama_menu }}</td><td>{{ $menu->kategori }}</td><td>Rp{{ number_format($menu->harga_jual, 0, ',', '.') }}</td><td><span class="badge badge-{{ $menu->status === 'Aktif' ? 'green' : 'muted' }}">{{ $menu->status }}</span></td></tr>
                @empty
                    <tr><td colspan="4" class="empty-state">Belum ada menu.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if ($menus->hasPages())<div class="pagination">{{ $menus->links() }}</div>@endif
    </section>
@endsection
