# Kedai Kopi Senja

## 1) Ringkasan

Proyek UKK RPL Soal 3 Varian C ini mencakup website staf Kedai Kopi Senja. Kasir mengelola pesanan dan struk; Manajer mengelola bahan, menu, resep, stok, harga, serta laporan.

## 2) Teknologi

- Laravel 9 dan PHP
- MySQL
- Blade untuk halaman web
- Vite dan npm untuk aset front-end
- PHPUnit untuk pengujian

## 3) Cara menjalankan server

1. Siapkan PHP, Composer, Node.js/npm, dan MySQL.
2. Dari folder proyek, jalankan perintah berikut di PowerShell:

   ```powershell
   composer install
   Copy-Item .env.example .env
   ```

3. Isi koneksi database di `.env` (`DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, dan `DB_PASSWORD`). Buat database MySQL terlebih dahulu.
4. Jalankan:

   ```powershell
   php artisan key:generate
   php artisan migrate
   php artisan db:seed --class=KategoriMenuSeeder
   php artisan impor:data-awal
   php artisan db:seed --class=StafSeeder
   php artisan db:seed
   php artisan db:seed --class=DemoSeeder
   npm install
   npm run build
   php artisan serve
   ```

5. Buka alamat server yang ditampilkan Artisan, biasanya `http://127.0.0.1:8000`.

Variabel integrasi Supplier (`SUPPLIER_URL` dan `SUPPLIER_API_KEY`) bersifat opsional. Jika belum memakai integrasi, biarkan nilainya kosong.

API key Supplier pada PDF UKK hanya digunakan server Laravel untuk integrasi harga bahan. Simpan di `.env` lokal dan jangan commit ke Git.

## 4) Akun uji

Akun staf uji:

| Peran | Username | Password |
|---|---|---|
| Manajer | `manajer` | `Manajer123` |
| Kasir | `kasir` | `Kasir123` |

Login web staf menggunakan username. `StafSeeder` menyediakan akun di atas.

Akun pelanggan demo untuk mencoba menu **Reset Password Pelanggan** pada Kasir:

| Nomor HP | Password awal |
|---|---|
| `081234567890` | `Pelanggan123` |

`PelangganSeeder` membuat akun ini lewat `php artisan db:seed`. Seeder tidak menimpa password akun yang sudah ada saat dijalankan ulang.

## 5) Letak berkas

| Isi | Lokasi |
|---|---|
| Migration database | `database/migrations/` |
| Seeder | `database/seeders/` |
| CSV data awal | `database/data/` |
| CSV master dan demo | `database/data-seeder-csv/` |
| Command impor CSV | `app/Console/Commands/ImporDataAwal.php` |
| Business service | `app/Services/` |
| Controller web | `app/Http/Controllers/Web/` |
| View Blade | `resources/views/` |
| Route web | `routes/web.php` |
| Dokumen perancangan | `docs/` |
| ERD hasil pemetaan migration | `docs/erd/erd.md` |
| Salinan diagram dan skenario UML | `docs/uml/` |

## 6) Keputusan saya

- Detail pesanan menyimpan snapshot `harga_saat_transaksi`, `nama_menu`, dan `resep_saat_transaksi`; perubahan harga atau resep kemudian tidak mengubah data snapshot yang sudah tersimpan.
- Pengembalian stok saat pembatalan memakai resep saat ini; bila resep diubah setelah pesanan dibuat, jumlah yang kembali bisa berbeda.
- Satuan bahan yang diterima adalah satuan dasar `g`, `ml`, dan `pcs`.
- Impor CSV mengubah `kg` ke `g` dan `liter` ke `ml`; harga per satuan dibagi dengan faktor konversinya.
- Pencocokan nama bahan resep dilakukan persis (tanpa membedakan kapital/spasi tepi) terlebih dahulu. Jika tidak cocok, `similar_text` hanya dipakai untuk satu kandidat terbaik dengan kemiripan minimal 80%; kandidat yang hilang atau ambigu dilewati dan dilaporkan.
- Menu menjadi `Habis` ketika stok salah satu bahan tidak cukup untuk membuat satu porsi. Menu `Nonaktif` tidak diaktifkan otomatis.
- Bahan ditandai menipis jika stoknya kurang dari atau sama dengan batas minimum.
- Jika sinkronisasi Supplier gagal, sistem melaporkan kegagalan dan tidak mengganti harga yang tersimpan.

## 7) Self-check 11 butir

Status di bawah merujuk pada kode yang ada di repo ini. “Sebagian” berarti ada bagian kriteria yang belum cocok dengan implementasi saat ini.

| Butir | Status | Bukti/lokasi kode |
|---:|---|---|
| 1. Kelola bahan, menu, dan resep | ✅ Selesai | `app/Http/Controllers/Web/ManajerController.php`, `routes/web.php`, `resources/views/manajer/` |
| 2. Stok masuk, batas minimum, dan bahan menipis | ✅ Selesai | `ManajerController.php`, `resources/views/manajer/stok-masuk.blade.php`, `batas-minimum.blade.php` |
| 3. Pesanan multi-item mengurangi stok; menu nonaktif/tanpa resep ditolak | ✅ Selesai | `app/Services/PesananService.php`, `tests/Feature/WebWorkflowTest.php` |
| 4. Kekurangan stok dan kebutuhan bahan gabungan ditangani | ✅ Selesai | `PesananService.php`, `tests/Feature/WebWorkflowTest.php` |
| 5. Status menu otomatis mengikuti ketersediaan stok | ✅ Selesai | `app/Services/MenuStatusService.php` |
| 6. Status berjenjang; pembatalan hanya saat `Baru` melalui route Kasir dan mengembalikan stok | ✅ Selesai | `PesananService.php`, `app/Http/Controllers/Web/PesananController.php`, `routes/web.php` |
| 7. Struk, service 5%, pajak 10% dari subtotal + service, dan snapshot harga | ✅ Selesai | `app/Services/PesananService.php` |
| 8. HPP dan margin | ✅ Selesai | `app/Services/HppService.php`, `ManajerController.php`, `resources/views/manajer/hpp-margin.blade.php` |
| 9. Laporan dan hak akses per peran | ✅ Selesai | `ManajerController.php`, `app/Http/Middleware/CekPeran.php`, `routes/web.php` |
| 10. Impor CSV melalui `impor:data-awal` | ✅ Selesai | `app/Console/Commands/ImporDataAwal.php`, `database/data/`, `database/seeders/KategoriMenuSeeder.php`, `database/seeders/DemoSeeder.php` |
| 11. Sinkronisasi harga Supplier dan fallback | ✅ Selesai; 20 harga berhasil disinkronkan | `app/Services/SupplierService.php`, `config/services.php`, `ManajerController.php` |
## 8) Pengecekan lanjutan

- Lengkapi self-check dengan klaim Ya/Tidak dan langkah uji singkat untuk setiap butir; cantumkan juga referensi dan bantuan yang digunakan secara jujur.

## 9) Referensi dan bantuan

- Dokumen rancangan: `docs/perancangan-annisa/Dokumentasi.md`
- Diagram dan skenario UML: `docs/uml/`
- ERD berdasarkan migration repo: `docs/erd/erd.md`
- Dokumentasi Laravel 9: <https://laravel.com/docs/9.x>
- Tes proyek: `php artisan test`
