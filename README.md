# Kedai Kopi Senja

## 1. Ringkasan

Proyek UKK RPL 2026/2027 Soal 3 Varian C ini berisi aplikasi Laravel untuk operasional staf Kedai Kopi Senja. Kasir mengelola pesanan, sedangkan Manajer mengelola bahan, menu, resep, stok, harga, dan laporan. **Aplikasi Android Pelanggan dan REST API untuk APK belum tersedia di repositori ini.**

## 2. Teknologi

- PHP 8.0.2 atau lebih baru dan Laravel 9
- MySQL
- Blade untuk aplikasi web staf
- Vite dan npm untuk aset front-end
- PHPUnit untuk pengujian

## 3. Cara menjalankan server

Petunjuk ini untuk Windows dengan PowerShell dan XAMPP.

1. Siapkan PHP, Composer, Node.js/npm, dan MySQL. Dari PowerShell, masuk ke folder proyek:

   ```powershell
   Set-Location C:\xampp1\htdocs\cafe
   composer install
   Copy-Item .env.example .env
   ```

2. Buat database MySQL bernama `cafe`. Isi pengaturan `DB_*` di `.env` sesuai MySQL lokal, lalu isi `APP_URL`. Atur `SUPPLIER_URL` dan `SUPPLIER_API_KEY` dengan alamat dan API key yang diberikan untuk ujian. Jangan menaruh API key di kode atau commit ke Git.

3. Siapkan aplikasi, impor data awal, dan isi data demo:

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
   ```

   Jalankan `DemoSeeder` setelah impor CSV. Seeder ini mengatur stok Susu UHT menjadi 0,3 liter (300 ml) dan dapat dijalankan ulang tanpa menggandakan data.

4. Jalankan server:

   ```powershell
   php artisan serve
   ```

   Buka alamat yang ditampilkan Artisan, biasanya `http://127.0.0.1:8000`. Jalankan pengujian dengan `php artisan test`.

## 4. Cara memasang APK

APK belum disediakan: repositori ini belum memiliki folder `apk/`, aplikasi Android, maupun endpoint REST API pelanggan. Karena itu belum ada APK yang dapat dipasang atau alamat server yang dapat diatur di APK. Butir penerimaan pelanggan yang mensyaratkan APK belum terpenuhi.

## 5. Akun uji

| Peran | Username / nomor HP | Password | Catatan |
|---|---|---|---|
| Manajer | `manajer` | `Manajer123` | Akun dibuat oleh `StafSeeder`. |
| Kasir | `kasir` | `Kasir123` | Akun dibuat oleh `StafSeeder`. |
| Pelanggan demo | `081234567890` | `Pelanggan123` | Record dibuat oleh `php artisan db:seed`; login pelanggan melalui APK belum tersedia. |

Login web hanya untuk Kasir dan Manajer. Pelanggan demo tersedia untuk mencoba fitur reset password dari halaman Kasir, bukan untuk login aplikasi pelanggan.

## 6. Letak berkas

| Isi | Lokasi / perintah |
|---|---|
| Migration | `database/migrations/` |
| CSV data awal | `database/data/` |
| Command impor CSV | `app/Console/Commands/ImporDataAwal.php`; jalankan `php artisan impor:data-awal` |
| Seeder kategori | `database/seeders/KategoriMenuSeeder.php`; jalankan sebelum impor |
| Seeder staf dan pelanggan demo | `database/seeders/StafSeeder.php`, `database/seeders/PelangganSeeder.php`; jalankan `php artisan db:seed --class=StafSeeder` dan `php artisan db:seed` |
| Seeder stok demo | `database/seeders/DemoSeeder.php`, `database/data-seeder-csv/seed_stok_demo.csv`; jalankan `php artisan db:seed --class=DemoSeeder` setelah impor |
| Business service | `app/Services/` |
| Controller web | `app/Http/Controllers/Web/` |
| Route web dan API | `routes/web.php`, `routes/api.php` (belum ada endpoint API pelanggan) |
| View Blade | `resources/views/` |
| ERD dan UML | `docs/erd/`, `docs/uml/` |

## 7. Keputusan dan penanganan data

- Satuan bahan di database menggunakan `g`, `ml`, dan `pcs`. Impor mengubah `kg` ke `g` dan `liter` ke `ml`; harga per satuan disesuaikan dengan faktor konversinya.
- Pencocokan nama bahan resep mengabaikan kapitalisasi dan spasi tepi. Jika tidak ada kecocokan persis, impor menerima hanya satu kandidat terbaik dengan `similar_text` minimal 80%. Baris dengan bahan tidak ditemukan, kecocokan ambigu, satuan tidak cocok, atau data tidak valid dilewati dan dilaporkan.
- Batas minimum bahan baru hasil impor adalah 0; impor ulang tidak mengubah batas minimum bahan yang sudah ada. Nama bahan yang hanya berbeda kapitalisasi atau spasi tepi dianggap sama.
- Registrasi/login pelanggan belum tersedia. Untuk pencarian reset password oleh Kasir, nomor HP dinormalisasi dengan membuang spasi, tanda kurung, titik, tanda hubung, dan awalan `+`; awalan `62` diubah menjadi `0`. Nomor hasil normalisasi harus diawali `0` dan memiliki 10–15 digit.
- Menu menjadi `Habis` jika stok salah satu bahan tidak cukup untuk satu porsi. Status `Nonaktif` yang dipilih Manajer tidak otomatis berubah menjadi `Aktif`.
- Detail transaksi menyimpan snapshot harga jual, nama menu, dan resep saat pesanan dibuat. Mengubah harga menu tidak mengubah struk lama.
- Pembatalan pesanan hanya diizinkan saat status `Baru` oleh Kasir. Stok yang dikembalikan dihitung memakai resep saat pembatalan; jika resep sudah berubah sejak transaksi dibuat, jumlah stok yang kembali dapat berbeda.
- Service charge adalah 5% dari subtotal dan pajak 10% dari subtotal ditambah service charge. Metode bayar `Tunai`/`QRIS` hanya dicatat, bukan diproses oleh sistem.

## 8. Lembar self-check

Klaim di bawah ini berdasarkan implementasi web yang ada. Butir 4 dan 7 adalah butir inti pada PDF.

| No. | Butir penerimaan | Klaim | Langkah uji singkat |
|---:|---|:---:|---|
| 1 | Manajemen bahan (satuan dasar dan nama unik), menu (termasuk nonaktif), dan resep multi-bahan | Ya | Login sebagai Manajer; kelola bahan, menu, dan resep; coba nama bahan yang berbeda kapitalisasi/spasi tepi. |
| 2 | Stok masuk, batas minimum per bahan, dan daftar bahan menipis | Ya | Catat stok masuk, atur batas minimum, lalu pastikan daftar menipis mengikuti stok dan batasnya. |
| 3 | Pesanan multi-item mengurangi stok sesuai resep; menu tanpa resep atau nonaktif ditolak | Ya | Login sebagai Kasir; buat pesanan beberapa item, lalu coba menu tanpa resep dan menu nonaktif. |
| 4 | Pesanan ditolak jika stok tidak cukup; kebutuhan bahan gabungan antar-item dihitung benar | Ya | Siapkan dua menu yang memakai bahan sama dengan stok di bawah total kebutuhan; pesan keduanya dan pastikan pesanan ditolak tanpa mengurangi stok. |
| 5 | Menu otomatis `Habis` jika bahan tidak cukup untuk satu porsi | Ya | Kurangi stok bahan resep di bawah kebutuhan satu porsi dan periksa status menu. |
| 6 | Status pesanan berjenjang; pembatalan hanya saat `Baru` oleh Kasir dan stok kembali | Ya | Ubah status `Baru` ke `Diproses` ke `Selesai`; coba loncat status dan batalkan pesanan `Baru`, lalu periksa stok. |
| 7 | Struk memuat subtotal, service 5%, pajak 10% dengan urutan benar, total, dan harga transaksi tetap | Ya | Buat pesanan, periksa rincian struk, ubah harga menu, lalu buka lagi struk lama. |
| 8 | HPP dan margin per menu berdasarkan resep | Ya | Login sebagai Manajer dan bandingkan HPP dengan jumlah takaran resep dikali harga bahan per satuan dasar. |
| 9 | Laporan dan hak akses per peran sesuai aturan | Ya | Buka menu/resep/stok/laporan sebagai Manajer; coba URL Manajer sebagai Kasir dan URL pesanan sebagai Manajer; pastikan daftar menu Kasir tidak menampilkan resep/HPP/margin. |
| 10 | Impor CSV menormalkan satuan, menangani salah ketik dan bahan tidak dikenal, serta memberi batas minimum awal 0 | Ya | Jalankan seeder kategori lalu `php artisan impor:data-awal`; periksa konversi, hasil pencocokan, ringkasan baris dilewati, dan batas minimum. |
| 11 | Supplier memakai API key dan mempertahankan harga terakhir bila sinkronisasi gagal | Ya | Simpan harga bahan, ubah konfigurasi Supplier menjadi tidak valid, jalankan sinkronisasi dari halaman HPP & Margin, lalu pastikan kegagalan dilaporkan dan harga tersimpan tidak berubah. |
| 12 | Akun pelanggan lengkap di APK: daftar, login, ubah password, reset oleh Kasir, dan logout | Tidak | Belum dapat diuji: APK dan REST API pelanggan belum tersedia. Fitur reset password Kasir di web saja tidak memenuhi butir ini. |
| 13 | Pelanggan memesan dari APK dengan nomor meja; aturan stok sama dan hanya satu pesanan `Baru` | Tidak | Belum dapat diuji: APK dan REST API pelanggan belum tersedia. |
| 14 | Pelanggan memantau status dan struk miliknya sendiri dari APK | Tidak | Belum dapat diuji: APK dan REST API pelanggan belum tersedia. |

### Fitur opsional

| Fitur | Dikerjakan |
|---|:---:|
| O1. Ukuran Reguler/Large | Tidak |
| O2. Promo happy hour | Tidak |
| O3. Penyesuaian stok dengan alasan | Tidak |
| O4. Laporan jumlah pesanan per jam | Tidak |
| O5. Reset password melalui email lokal | Tidak |

## 9. Belum selesai / keterbatasan

- Belum ada APK Android, endpoint REST API pelanggan, atau alur daftar/login/pemesanan pelanggan melalui aplikasi. Oleh sebab itu, butir penerimaan 12–14 belum terpenuhi dan proyek ini belum memenuhi seluruh ketentuan hasil akhir pada PDF.
- Tidak ada fitur opsional O1–O5 yang dikerjakan.
- Pengembalian stok setelah resep berubah menggunakan resep terbaru, bukan snapshot resep transaksi.

## 10. Referensi dan Bantuan

- Dokumen kebutuhan UKK: `docs/perancangan-annisa/UKK_RPL_2026-2027_Soal_3_Varian_C_Kafe (1).pdf`.
- Dokumentasi Laravel 9: <https://laravel.com/docs/9.x>.
- Kode, migration, seeder, dan pengujian pada repositori ini digunakan untuk memeriksa klaim self-check.
- Copilot dan Claude (AI) membantu menyusun serta merapikan README berdasarkan PDF dan kode proyek. Isi dan status fitur dicocokkan kembali dengan berkas proyek; AI tidak digunakan untuk mengklaim fitur APK yang belum ada.
