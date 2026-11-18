@extends('layouts.app')

@section('title', 'Kelola Resep')
@section('heading', 'Kelola Resep')
@section('description', 'Atur bahan dan takaran untuk resep setiap menu.')

@section('content')
    <section class="panel form-panel">
        <div class="panel-head panel-head-inline">
            <div><h2>Resep menu</h2><p class="panel-hint">Pilih menu untuk menambah atau mengubah bahan resep.</p></div>
            @if ($menus->isNotEmpty())
                <form method="GET" action="{{ route('manajer.resep') }}" class="inline-form">
                    <label for="recipe-menu">Pilih menu</label>
                    <select id="recipe-menu" name="menu_id" onchange="this.form.submit()" required>
                        @foreach ($menus as $menu)
                            <option value="{{ $menu->id }}" @selected($selectedMenu?->id === $menu->id)>{{ $menu->nama_menu }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>

        @if (! $selectedMenu)
            <p class="empty-state">Belum ada menu. <a href="{{ route('manajer.menu') }}">Tambah menu</a> sebelum menyusun resep.</p>
        @elseif ($bahans->isEmpty())
            <p class="empty-state">Belum ada bahan. <a href="{{ route('manajer.bahan') }}">Tambah bahan</a> sebelum menyusun resep.</p>
        @elseif ($selectedMenu->resep->isEmpty() && $bahans->isEmpty())
            <p class="empty-state">Belum ada bahan. <a href="{{ route('manajer.bahan') }}">Tambah bahan</a> untuk membuat resep.</p>
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
                        <thead><tr><th>Bahan</th><th>Takaran</th><th>Satuan dasar</th></tr></thead>
                        <tbody id="recipe-rows">
                        @foreach ($bahans as $bahan)
                            @php($takaran = $resepForm[$bahan->id] ?? null)
                            @if ($takaran !== null && $takaran !== '')
                                <tr data-recipe-row data-bahan-id="{{ $bahan->id }}">
                                    <td>{{ $bahan->nama_bahan }}</td>
                                    <td><input class="table-input" type="number" name="bahan[{{ $bahan->id }}]" value="{{ $takaran }}" min="0" step="0.001" required></td>
                                    <td>{{ $bahan->satuan }}</td>
                                </tr>
                            @endif
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @if ($bahans->isNotEmpty())
                    <div class="recipe-add-row">
                        <label>Tambah bahan ke resep
                            <select id="recipe-material" required>
                                <option value="">Pilih bahan</option>
                                @foreach ($bahans as $bahan)
                                    <option value="{{ $bahan->id }}" data-name="{{ $bahan->nama_bahan }}" data-unit="{{ $bahan->satuan }}">{{ $bahan->nama_bahan }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Takaran
                            <span class="recipe-amount-control">
                                <input id="recipe-amount" type="number" min="0.001" step="0.001" placeholder="0" required>
                                <span id="recipe-unit" class="recipe-unit" aria-live="polite">-</span>
                            </span>
                        </label>
                        <button class="button button-secondary" type="button" id="add-recipe-material">Tambah</button>
                    </div>
                @else
                    <p class="empty-state">Belum ada bahan yang dapat ditambahkan.</p>
                @endif
            </form>
            <div class="table-actions">
                <button class="button button-primary" type="submit" form="recipe-form">Simpan Resep</button>
            </div>
        @endif
    </section>
@endsection
