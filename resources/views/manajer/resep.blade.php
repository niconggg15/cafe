@extends('layouts.app')

@section('title', 'Kelola Resep')
@section('heading', 'Kelola Resep')
@section('description', 'Satu menu terdiri dari satu atau lebih bahan dengan takaran.')

@section('content')
    <section class="panel form-panel">
        <div class="panel-head panel-head-inline">
            <div><h2>Resep menu</h2><p class="panel-hint">Pilih menu untuk mengubah daftar bahan dan takaran.</p></div>
            <form method="GET" action="{{ route('manajer.resep') }}" class="inline-form">
                <label for="recipe-menu">Pilih menu</label>
                <select id="recipe-menu" name="menu_id" onchange="this.form.submit()" required>
                    @foreach ($menus as $menu)
                        <option value="{{ $menu->id }}" @selected($selectedMenu?->id === $menu->id)>{{ $menu->nama_menu }}</option>
                    @endforeach
                </select>
            </form>
        </div>

        @if (! $selectedMenu)
            <p class="empty-state">Tambahkan menu dan bahan sebelum menyusun resep.</p>
        @elseif ($bahans->isEmpty())
            <p class="empty-state">Tambahkan bahan sebelum menyusun resep.</p>
        @else
            @php($resepForm = old('bahan', $selectedMenu->resep->mapWithKeys(fn ($resep) => [$resep->bahan_id => $resep->takaran])->all()))
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
                <div class="recipe-add-row">
                    <label>Tambah bahan ke resep
                        <select id="recipe-material" required>
                            <option value="">Pilih bahan</option>
                            @foreach ($bahans as $bahan)
                                <option value="{{ $bahan->id }}" data-name="{{ $bahan->nama_bahan }}" data-unit="{{ $bahan->satuan }}">{{ $bahan->nama_bahan }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label>Takaran<input id="recipe-amount" type="number" min="0.001" step="0.001" placeholder="0" required></label>
                    <button class="button button-secondary" type="button" id="add-recipe-material">Tambah</button>
                </div>
                <button class="button button-primary" type="submit">Simpan Resep</button>
            </form>
        @endif
    </section>
@endsection
