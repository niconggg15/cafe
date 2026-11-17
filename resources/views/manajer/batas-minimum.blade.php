@extends('layouts.app')

@section('title', 'Batas Minimum')
@section('heading', 'Batas Minimum dan Bahan Menipis')
@section('description', 'Bahan menipis bila stok sama dengan atau kurang dari batas minimum')

@section('content')
    <section class="panel">
        <div class="table-wrap"><table>
            <thead><tr><th>Bahan</th><th>Stok</th><th>Batas minimum</th><th>Satuan</th><th>Status</th><th>Aksi</th></tr></thead>
            <tbody>
            @forelse ($bahans as $bahan)
                <tr>
                    <td class="table-primary">{{ $bahan->nama_bahan }}</td>
                    <td>{{ rtrim(rtrim(number_format($bahan->stok, 3, ',', '.'), '0'), ',') }}</td>
                    <td>
                        <input class="table-input" type="number" name="batas_minimum" value="{{ $bahan->batas_minimum }}" min="0" step="0.001" form="batas-minimum-{{ $bahan->id }}" required>
                    </td>
                    <td>{{ $bahan->satuan }}</td>
                    <td>
                        @if ($bahan->stok <= $bahan->batas_minimum)
                            <span class="badge badge-amber">Menipis</span>
                        @else
                            <span class="badge badge-green">Aman</span>
                        @endif
                    </td>
                    <td>
                        <form id="batas-minimum-{{ $bahan->id }}" method="POST" action="{{ route('manajer.batas-minimum.update', $bahan) }}">
                            @csrf @method('PUT')
                            <button class="button button-small button-secondary" type="submit">Simpan</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="empty-state">Belum ada bahan.</td></tr>
            @endforelse
            </tbody>
        </table></div>
        <p class="panel-footnote">Daftar menipis langsung mengikuti perubahan stok dan batas minimum. Batas awal setelah impor: 0.</p>
    </section>
@endsection
