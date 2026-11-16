<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\KasirController;
use App\Http\Controllers\Web\ManajerController;
use App\Http\Controllers\Web\PesananController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'form'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::middleware(['auth', 'wajib-ganti-password'])->group(function () {
    Route::get('/ganti-password', [AuthController::class, 'changePasswordForm'])->name('password.change');
    Route::put('/ganti-password', [AuthController::class, 'changePassword'])->name('password.update');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/', function () {
        return auth()->user()->role === 'Manajer'
            ? redirect()->route('manajer.bahan')
            : redirect()->route('kasir.pesanan.baru');
    })->name('dashboard');

    Route::middleware('peran:Kasir')->prefix('')->name('kasir.')->group(function () {
        Route::get('/pesanan/baru', [PesananController::class, 'baru'])->name('pesanan.baru');
        Route::post('/pesanan', [PesananController::class, 'simpan'])->name('pesanan.simpan');
        Route::get('/pesanan', [PesananController::class, 'index'])->name('pesanan.index');
        Route::put('/pesanan/{pesanan}/status', [PesananController::class, 'status'])->name('pesanan.status');
        Route::post('/pesanan/{pesanan}/batal', [PesananController::class, 'batalkan'])->name('pesanan.batal');
        Route::get('/pesanan/{pesanan}/struk', [PesananController::class, 'struk'])->name('struk');
        Route::get('/daftar-menu', [KasirController::class, 'daftarMenu'])->name('menu');
        Route::get('/pelanggan/reset', [KasirController::class, 'resetPasswordForm'])->name('reset-password');
        Route::post('/pelanggan/reset', [KasirController::class, 'resetPassword'])->name('reset-password.simpan');
    });

    Route::middleware('peran:Manajer')->prefix('manajer')->name('manajer.')->group(function () {
        Route::get('/bahan', [ManajerController::class, 'bahan'])->name('bahan');
        Route::post('/bahan', [ManajerController::class, 'simpanBahan'])->name('bahan.simpan');
        Route::patch('/bahan/{bahan}/status', [ManajerController::class, 'ubahStatusBahan'])->name('bahan.status');
        Route::get('/menu', [ManajerController::class, 'menu'])->name('menu');
        Route::post('/menu', [ManajerController::class, 'simpanMenu'])->name('menu.simpan');
        Route::put('/menu/{menu}', [ManajerController::class, 'simpanMenu'])->name('menu.update');
        Route::get('/resep', [ManajerController::class, 'resep'])->name('resep');
        Route::post('/resep', [ManajerController::class, 'simpanResep'])->name('resep.simpan');
        Route::get('/stok-masuk', [ManajerController::class, 'stokMasuk'])->name('stok-masuk');
        Route::post('/stok-masuk', [ManajerController::class, 'simpanStokMasuk'])->name('stok-masuk.simpan');
        Route::get('/batas-minimum', [ManajerController::class, 'batasMinimum'])->name('batas-minimum');
        Route::put('/batas-minimum/{bahan}', [ManajerController::class, 'simpanBatasMinimum'])->name('batas-minimum.update');
        Route::get('/hpp-margin', [ManajerController::class, 'hppMargin'])->name('hpp-margin');
        Route::put('/hpp-margin/{menu}', [ManajerController::class, 'simpanHarga'])->name('hpp-margin.update');
        Route::post('/hpp-margin/sinkron', [ManajerController::class, 'sinkronSupplier'])->name('hpp-margin.sinkron');
        Route::get('/laporan', [ManajerController::class, 'laporan'])->name('laporan');
    });
});
