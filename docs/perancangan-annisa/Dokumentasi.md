# Dokumentasi Per Bagian — Sistem Informasi Kedai Kopi Senja

Dokumen ini memecah `Dokumentasi.md` menjadi bagian-bagian terpisah supaya mudah dicari saat mengerjakan dan saat wawancara.

> Nama file, class, route, dan akun di bawah adalah **usulan**. Samakan dengan kode yang benar-benar kamu buat. Bagian yang bergantung pada dokumen hari 1 (alamat Supplier, API key, nama header) ditandai `[ISI]`.

## Daftar Isi

| Bagian | Isi |
|---|---|
| A | Cek versi dan catatan Laravel 10 vs 11+ |
| B | **Terminal** — semua perintah (XAMPP + Windows) |
| C | **Database** — tabel, relasi, constraint |
| D | **Back End** — model, service, controller, middleware, route, aturan bisnis |
| E | **Front End Web** — halaman Kasir dan Manajer (Blade) |
| F | **APK Android** — layar, endpoint, pengaturan server |
| G | **CLI** — impor CSV dan seeder |
| H | **Git dan pengumpulan** — commit, struktur repo, README |
| I | Peta 14 butir penerimaan ke bagian dokumen ini |
| J | Masalah yang sering muncul |

---

## A. Cek versi dulu (penting)

```powershell
php artisan --version
php -v
```

Beberapa perintah berbeda antara Laravel 10 dan Laravel 11 ke atas. Ikuti kolom yang sesuai.

| Hal | Laravel 10 | Laravel 11+ |
|---|---|---|
| Pasang API + Sanctum | `composer require laravel/sanctum` lalu `php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"` | `php artisan install:api` |
| Daftar alias middleware | `app/Http/Kernel.php` → `$middlewareAliases` | `bootstrap/app.php` → `withMiddleware` |
| File `routes/api.php` | sudah ada | dibuat oleh `install:api` |
| Buat class biasa (service) | buat file manual di `app/Services` | `php artisan make:class Services/SupplierService` |
| Perintah Artisan | otomatis terdaftar dari `app/Console/Commands` | sama |

---

## B. Terminal

Semua perintah dijalankan dari folder project (`cafe`) di terminal VS Code (PowerShell).

### B1. Persiapan XAMPP

1. Buka XAMPP Control Panel → **Start** `MySQL` (Apache tidak wajib, karena memakai `php artisan serve`).
2. Bila `php` tidak dikenali, tambahkan `C:\xampp\php` ke PATH Windows, lalu buka ulang terminal.
3. Bila `mysql` tidak dikenali, pakai path penuh: `C:\xampp\mysql\bin\mysql.exe -u root`.

```powershell
php -v
composer -V
git --version
php -m | findstr /i "pdo_mysql mbstring openssl"
```

> XAMPP biasanya memakai MariaDB, bukan MySQL. Constraint `CHECK` didukung MariaDB 10.2 ke atas, jadi aman.

### B2. `.env`

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cafe
DB_USERNAME=root
DB_PASSWORD=

SUPPLIER_URL=[ISI alamat layanan dari hari 1]
SUPPLIER_API_KEY=[ISI API key dari hari 1]
```

Setiap `.env` berubah:

```powershell
php artisan config:clear
```

### B3. Membuat file (generator)

```powershell
# Model + migrasi
php artisan make:model Bahan -m
php artisan make:model Menu -m
php artisan make:model Resep -m
php artisan make:model StokMasuk -m
php artisan make:model Pesanan -m
php artisan make:model DetailPesanan -m
php artisan make:migration add_kolom_kafe_ke_users_table --table=users

# Controller web
php artisan make:controller Web/AuthController
php artisan make:controller Web/PesananController
php artisan make:controller Web/MenuController
php artisan make:controller Web/BahanController
php artisan make:controller Web/ResepController
php artisan make:controller Web/StokMasukController
php artisan make:controller Web/LaporanController
php artisan make:controller Web/PelangganController

# Controller API (APK)
php artisan make:controller Api/AuthController
php artisan make:controller Api/MenuController
php artisan make:controller Api/PesananController

# Lainnya
php artisan make:middleware CekPeran
php artisan make:middleware WajibGantiPassword
php artisan make:command ImporDataAwal --command=impor:data-awal
php artisan make:seeder StafSeeder
php artisan make:seeder DemoSeeder
php artisan make:exception StokKurangException
```

Urutan migrasi harus: `users` → `bahan` → `menu` → `resep` → `stok_masuk` → `pesanan` → `detail_pesanan`. Bila urutan salah, ganti nama file migrasi (angka tanggal di depan) agar berurutan.

### B4. Menjalankan database dan data

Urutan harus tepat:

```powershell
php artisan migrate                              # 1. buat tabel
php artisan impor:data-awal                      # 2. impor CSV (bahan -> menu -> resep)
php artisan db:seed --class=StafSeeder           # 3. akun Manajer dan Kasir
php artisan db:seed --class=DemoSeeder           # 4. data demo (Susu UHT = 300 ml)
```

Mengulang dari awal:

```powershell
php artisan migrate:fresh
php artisan impor:data-awal
php artisan db:seed --class=StafSeeder
php artisan db:seed --class=DemoSeeder
```

### B5. Menjalankan server dan memeriksa

```powershell
php artisan serve --host=0.0.0.0 --port=8000
php artisan route:list
php artisan route:list --path=api
php artisan tinker
```

Cek cepat di `tinker`:

```php
App\Models\Bahan::count();
App\Models\Bahan::where('nama_bahan','Susu UHT')->value('stok');   // 300
App\Models\Bahan::where('batas_minimum','!=',0)->count();          // 0
```

Log penolakan impor dan error:

```powershell
Get-Content storage\logs\laravel.log -Tail 50
```

### B6. Uji API lewat terminal

Di PowerShell, pakai `curl.exe` (bukan `curl`, karena `curl` adalah alias `Invoke-WebRequest`).

```powershell
# Daftar
curl.exe -X POST http://localhost:8000/api/register -H "Accept: application/json" -d "no_hp=081100000003" -d "password=Pelanggan123"

# Login (hasilnya token)
curl.exe -X POST http://localhost:8000/api/login -H "Accept: application/json" -d "no_hp=081100000003" -d "password=Pelanggan123"

# Pesanan sendiri
curl.exe http://localhost:8000/api/pesanan -H "Accept: application/json" -H "Authorization: Bearer TOKEN"

# Pesanan milik orang lain (harus 403)
curl.exe -i http://localhost:8000/api/pesanan/ID_ORANG_LAIN -H "Accept: application/json" -H "Authorization: Bearer TOKEN"
```

### B7. Jaringan untuk APK

```powershell
ipconfig        # cari IPv4 Address, misalnya 192.168.1.10
netsh advfirewall firewall add rule name="Laravel 8000" dir=in action=allow protocol=TCP localport=8000   # PowerShell Administrator
adb install apk\NAMA_APK.apk    # opsional, lewat kabel USB
```

Tes dari browser HP: `http://192.168.1.10:8000`. Bila halaman muncul, server terjangkau.

---

## C. Database

### C1. Tabel dan kolom

| Tabel | Kolom penting |
|---|---|
| `users` | `nama`, `no_hp` (unik, sudah ternormalisasi), `password` (hash), `role` (Kasir/Manajer/Pelanggan), `wajib_ganti_password` (bool, default false) |
| `bahan` | `nama_bahan` (unik), `satuan` (g/ml/pcs), `stok`, `batas_minimum` (default 0), `harga_per_satuan`, `harga_diperbarui_at` |
| `menu` | `nama_menu`, `harga_jual`, `status` (Aktif/Habis/Nonaktif) |
| `resep` | `menu_id`, `bahan_id`, `takaran` — unik pasangan (`menu_id`, `bahan_id`) |
| `stok_masuk` | `bahan_id`, `user_id`, `tanggal`, `jumlah` |
| `pesanan` | `pelanggan_id` (nullable), `kasir_id` (nullable), `no_meja`, `metode_bayar`, `status`, `subtotal`, `service_charge`, `pajak`, `total_bayar` |
| `detail_pesanan` | `pesanan_id`, `menu_id`, `nama_menu`, `jumlah`, `harga_saat_transaksi` |

### C2. Relasi

- `Menu` 1—N `Resep` N—1 `Bahan` (relasi banyak ke banyak antara menu dan bahan lewat resep).
- `Pesanan` 1—N `DetailPesanan` N—1 `Menu`.
- `Pesanan` N—1 `User` (pelanggan) dan N—1 `User` (kasir).
- `Bahan` 1—N `StokMasuk` N—1 `User` (manajer).

### C3. Constraint (tulis di migrasi)

Dipakai penguji pada penilaian "kualitas teknis".

```php
// di akhir up() migrasi pesanan
DB::statement("ALTER TABLE pesanan ADD CONSTRAINT chk_meja CHECK (no_meja BETWEEN 1 AND 10)");
DB::statement("ALTER TABLE bahan ADD CONSTRAINT chk_stok CHECK (stok >= 0)");
DB::statement("ALTER TABLE resep ADD CONSTRAINT chk_takaran CHECK (takaran > 0)");
```

Untuk kolom berpilihan, pakai `enum` atau `string` + validasi:

```php
$table->enum('role', ['Kasir','Manajer','Pelanggan']);
$table->enum('status', ['Baru','Diproses','Selesai','Dibatalkan']);
$table->enum('metode_bayar', ['Tunai','QRIS']);
```

Catatan:
- `nama_bahan` unik tidak peka huruf besar/kecil bila collation `utf8mb4_unicode_ci`, tetapi spasi di awal/akhir tidak otomatis dibuang. Lakukan `trim()` di kode **sebelum** simpan.
- `detail_pesanan.nama_menu` dan `harga_saat_transaksi` adalah salinan saat transaksi. Inilah cara menyimpan harga agar struk lama tidak berubah (keputusan yang diminta soal; tulis di README).
- ERD harus sesuai tabel ini; simpan di `/docs/erd`.

---

## D. Back End

### D1. Struktur folder

```
app/
├── Console/Commands/ImporDataAwal.php
├── Exceptions/StokKurangException.php
├── Http/
│   ├── Controllers/
│   │   ├── Web/   (Auth, Pesanan, Menu, Bahan, Resep, StokMasuk, Laporan, Pelanggan)
│   │   └── Api/   (Auth, Menu, Pesanan)
│   └── Middleware/ (CekPeran, WajibGantiPassword)
├── Models/        (User, Bahan, Menu, Resep, StokMasuk, Pesanan, DetailPesanan)
└── Services/
    ├── PesananService.php      (buat, ubah status, batal)
    ├── MenuStatusService.php   (Aktif <-> Habis otomatis)
    ├── HppService.php          (HPP dan margin)
    ├── SupplierService.php     (harga dari Supplier + fallback)
    └── NomorHp.php             (normalisasi dan validasi)
```

Prinsip: controller tipis (validasi + panggil service + kembalikan view/JSON). Aturan bisnis ada di `Services`, supaya web dan API memakai logika yang sama persis. Ini cocok dengan aturan "pesanan dari APK sama dengan dari Kasir".

### D2. Relasi model (ringkas)

```php
// Menu
public function resep()  { return $this->hasMany(Resep::class); }
// Resep
public function menu()   { return $this->belongsTo(Menu::class); }
public function bahan()  { return $this->belongsTo(Bahan::class); }
// Bahan
public function resep()  { return $this->hasMany(Resep::class); }
// Pesanan
public function detail() { return $this->hasMany(DetailPesanan::class); }
public function pelanggan() { return $this->belongsTo(User::class, 'pelanggan_id'); }
// User: pasang trait HasApiTokens (Sanctum)
```

### D3. Middleware

`CekPeran` — hak akses diperiksa di server (web **dan** API):

```php
public function handle(Request $request, Closure $next, string ...$peran)
{
    $user = $request->user();
    if (!$user || !in_array($user->role, $peran, true)) {
        abort(403);
    }
    return $next($request);
}
```

`WajibGantiPassword` — dipasang di route API; bila `wajib_ganti_password = true`, tolak semua endpoint kecuali `ganti-password` dan `logout`:

```php
if ($request->user()->wajib_ganti_password) {
    return response()->json(['message' => 'Wajib ganti password'], 403);
}
```

Daftarkan alias `peran` dan `wajib.ganti` sesuai tabel di Bagian A.

### D4. Route

**`routes/web.php`**

```php
Route::get('/login', [AuthController::class, 'form'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/ganti-password', [AuthController::class, 'formGanti']);
    Route::post('/ganti-password', [AuthController::class, 'ganti']);

    Route::middleware('peran:Kasir')->group(function () {
        Route::get('/pesanan/baru', [PesananController::class, 'baru']);
        Route::post('/pesanan', [PesananController::class, 'simpan']);
        Route::get('/pesanan', [PesananController::class, 'index']);
        Route::get('/pesanan/{pesanan}/struk', [PesananController::class, 'struk']);
        Route::post('/pesanan/{pesanan}/status', [PesananController::class, 'ubahStatus']);
        Route::post('/pesanan/{pesanan}/batal', [PesananController::class, 'batal']);
        Route::get('/daftar-menu', [MenuController::class, 'daftarKasir']);   // tanpa resep, HPP, margin
        Route::get('/pelanggan/reset', [PelangganController::class, 'form']);
        Route::post('/pelanggan/reset', [PelangganController::class, 'reset']);
    });

    Route::middleware('peran:Manajer')->prefix('manajer')->group(function () {
        Route::resource('bahan', BahanController::class);
        Route::resource('menu', MenuController::class);
        Route::resource('resep', ResepController::class);
        Route::get('stok-masuk', [StokMasukController::class, 'index']);
        Route::post('stok-masuk', [StokMasukController::class, 'simpan']);
        Route::get('batas-minimum', [BahanController::class, 'batasMinimum']);
        Route::post('batas-minimum/{bahan}', [BahanController::class, 'ubahBatas']);
        Route::get('hpp-margin', [LaporanController::class, 'hppMargin']);
        Route::post('hpp-margin/sinkron', [LaporanController::class, 'sinkronSupplier']);
        Route::get('laporan', [LaporanController::class, 'index']);
    });
});
```

Kasir dan Manajer sama-sama membuka `/ganti-password`. Halaman Manajer dicek di **route**, jadi membuka lewat alamat langsung tetap ditolak 403 (butir 9).

**`routes/api.php`** (dipakai APK)

```php
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum', 'peran:Pelanggan'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/ganti-password', [AuthController::class, 'gantiPassword']);

    Route::middleware('wajib.ganti')->group(function () {
        Route::get('/menu', [MenuController::class, 'index']);
        Route::post('/pesanan', [PesananController::class, 'simpan']);
        Route::get('/pesanan', [PesananController::class, 'index']);          // milik sendiri
        Route::get('/pesanan/{pesanan}', [PesananController::class, 'show']); // cek pemilik
        Route::get('/pesanan/{pesanan}/struk', [PesananController::class, 'struk']);
    });
});
```

Pemilik pesanan dicek di controller:

```php
abort_if($pesanan->pelanggan_id !== $request->user()->id, 403);
```

### D5. Nomor HP dan akun

Aturan dari soal: tolak huruf, tolak kurang dari 10 digit, nomor tidak boleh ganda walau formatnya beda (`0812…` dan `+62812…`). Keputusan format lain tulis di README.

```php
class NomorHp
{
    public static function normal(string $raw): ?string
    {
        $n = preg_replace('/[\s\-().]/', '', trim($raw));
        $n = ltrim($n, '+');
        if (!ctype_digit($n)) return null;                       // ada huruf/simbol lain
        if (str_starts_with($n, '62')) $n = '0' . substr($n, 2);
        if (!str_starts_with($n, '0')) return null;
        $len = strlen($n);
        return ($len >= 10 && $len <= 15) ? $n : null;
    }
}
```

Registrasi: normalkan → validasi → cek unik pada hasil normalisasi → `Hash::make` → simpan `wajib_ganti_password = false`.
Reset oleh Kasir: buat password sementara acak, `Hash::make`, set `wajib_ganti_password = true`, tampilkan password sementara **sekali** di layar Kasir.

### D6. `PesananService` — inti butir 3, 4, 5, 6, 7

```php
public function buat(array $items, ?int $pelangganId, ?int $kasirId, int $noMeja, string $metode): Pesanan
{
    return DB::transaction(function () use ($items, $pelangganId, $kasirId, $noMeja, $metode) {

        // 0. Pelanggan hanya boleh punya 1 pesanan berstatus Baru
        if ($pelangganId && Pesanan::where('pelanggan_id', $pelangganId)->where('status', 'Baru')->lockForUpdate()->exists()) {
            throw new DomainException('Masih ada pesanan berstatus Baru.');
        }

        // 1. Gabungkan baris menu yang sama
        $porsi = collect($items)->groupBy('menu_id')->map(fn ($g) => (int) $g->sum('jumlah'));
        $menus = Menu::with('resep')->whereIn('id', $porsi->keys())->get()->keyBy('id');

        // 2. Validasi tiap menu: ada, Aktif, punya resep
        foreach ($porsi as $menuId => $n) {
            $m = $menus[$menuId] ?? null;
            if (!$m || $m->status === 'Nonaktif' || $m->resep->isEmpty()) {
                throw new DomainException('Menu tidak dapat dipesan.');
            }
        }

        // 3. Kebutuhan GABUNGAN per bahan (butir 4)
        $butuh = [];
        foreach ($menus as $m) {
            foreach ($m->resep as $r) {
                $butuh[$r->bahan_id] = ($butuh[$r->bahan_id] ?? 0) + $r->takaran * $porsi[$m->id];
            }
        }

        // 4. Kunci baris bahan, bandingkan stok
        $bahan = Bahan::whereIn('id', array_keys($butuh))->lockForUpdate()->get()->keyBy('id');
        $kurang = [];
        foreach ($butuh as $id => $perlu) {
            if ($bahan[$id]->stok < $perlu) {
                $kurang[] = ['bahan' => $bahan[$id]->nama_bahan, 'butuh' => $perlu, 'tersedia' => $bahan[$id]->stok, 'satuan' => $bahan[$id]->satuan];
            }
        }
        if ($kurang) throw new StokKurangException($kurang);   // tampilkan bahan yang kurang

        // 5. Potong stok
        foreach ($butuh as $id => $perlu) {
            $bahan[$id]->decrement('stok', $perlu);
        }

        // 6. Biaya: service 5% dari subtotal, pajak 10% dari (subtotal + service)
        $subtotal = $menus->sum(fn ($m) => $m->harga_jual * $porsi[$m->id]);
        $service  = round($subtotal * 0.05);
        $pajak    = round(($subtotal + $service) * 0.10);

        $p = Pesanan::create([
            'pelanggan_id' => $pelangganId, 'kasir_id' => $kasirId,
            'no_meja' => $noMeja, 'metode_bayar' => $metode, 'status' => 'Baru',
            'subtotal' => $subtotal, 'service_charge' => $service,
            'pajak' => $pajak, 'total_bayar' => $subtotal + $service + $pajak,
        ]);
        foreach ($menus as $m) {
            $p->detail()->create([
                'menu_id' => $m->id, 'nama_menu' => $m->nama_menu,
                'jumlah' => $porsi[$m->id], 'harga_saat_transaksi' => $m->harga_jual,   // snapshot harga
            ]);
        }

        // 7. Segarkan status Aktif/Habis untuk menu yang memakai bahan tersebut
        app(MenuStatusService::class)->segarkan(array_keys($butuh));

        return $p;
    });
}
```

Cek dengan contoh soal: subtotal Rp100.000 → service Rp5.000 → pajak Rp10.500 → total Rp115.500.

**Ubah status** (butir 6) — hanya urutan satu langkah:

```php
const URUTAN = ['Baru' => 'Diproses', 'Diproses' => 'Selesai'];

public function ubahStatus(Pesanan $p, string $tujuan): void
{
    if ((self::URUTAN[$p->status] ?? null) !== $tujuan) {
        throw new DomainException('Status tidak boleh loncat.');
    }
    $p->update(['status' => $tujuan]);
}
```

**Batal** — hanya `Baru`, hanya Kasir (dijaga `peran:Kasir` di route), stok kembali:

```php
public function batal(Pesanan $p): void
{
    DB::transaction(function () use ($p) {
        $p = Pesanan::lockForUpdate()->findOrFail($p->id);
        if ($p->status !== 'Baru') throw new DomainException('Hanya pesanan Baru yang dapat dibatalkan.');

        $balik = [];
        foreach ($p->detail()->with('menu.resep')->get() as $d) {
            foreach ($d->menu->resep as $r) {
                $balik[$r->bahan_id] = ($balik[$r->bahan_id] ?? 0) + $r->takaran * $d->jumlah;
            }
        }
        foreach ($balik as $bahanId => $jumlah) Bahan::whereKey($bahanId)->increment('stok', $jumlah);

        $p->update(['status' => 'Dibatalkan']);
        app(MenuStatusService::class)->segarkan(array_keys($balik));
    });
}
```

> Catatan untuk README: pengembalian stok memakai resep saat ini. Bila resep diubah setelah pesanan dibuat, jumlah yang kembali bisa berbeda. Tulis ini sebagai keputusan atau keterbatasan.

### D7. `MenuStatusService` (butir 5)

Aturan: menu `Habis` bila bahannya tidak cukup untuk **1 porsi**; kembali `Aktif` bila sudah cukup. Menu `Nonaktif` tidak boleh disentuh.

```php
public function segarkan(array $bahanIds): void
{
    $menus = Menu::whereHas('resep', fn ($q) => $q->whereIn('bahan_id', $bahanIds))
                 ->with('resep.bahan')->where('status', '!=', 'Nonaktif')->get();
    foreach ($menus as $m) {
        $cukup = $m->resep->every(fn ($r) => $r->bahan->stok >= $r->takaran);
        $m->update(['status' => $cukup ? 'Aktif' : 'Habis']);
    }
}
```

Panggil juga setelah: stok masuk, perubahan resep, aktifkan kembali menu.

### D8. `SupplierService` dan HPP (butir 8 dan 11)

```php
public function sinkron(): array
{
    $cfg = config('services.supplier');
    try {
        $res = Http::withHeaders(['X-API-Key' => $cfg['api_key']])   // [ISI nama header sesuai dokumentasi hari 1]
                   ->timeout($cfg['timeout'])->get($cfg['url']);
        if (!$res->successful()) throw new RuntimeException('Supplier menolak: ' . $res->status());

        foreach ($res->json() as $row) {                              // [ISI bentuk JSON sesuai dokumentasi]
            Bahan::where('nama_bahan', $row['nama'])->update([
                'harga_per_satuan' => $row['harga'], 'harga_diperbarui_at' => now(),
            ]);
        }
        return ['ok' => true, 'pesan' => 'Harga diperbarui dari Supplier.'];
    } catch (\Throwable $e) {
        Log::warning('Supplier gagal: ' . $e->getMessage());
        return ['ok' => false, 'pesan' => 'Supplier tidak dapat dihubungi, memakai harga terakhir.'];
    }
}
```

- Layanan **mati, salah alamat, atau API key salah** harus tetap jatuh ke `catch`; sistem tetap jalan dengan harga terakhir dan **menampilkan tanggal** `harga_diperbarui_at`.
- Dipanggil dari server, **bukan** dari APK.
- Alamat dan key dibaca lewat `config('services.supplier')` (daftarkan di `config/services.php`):

```php
'supplier' => ['url' => env('SUPPLIER_URL'), 'api_key' => env('SUPPLIER_API_KEY'), 'timeout' => 5],
```

HPP dan margin:

```php
// HPP = jumlah (takaran × harga_per_satuan); margin = harga_jual − HPP
$hpp = $menu->resep->sum(fn ($r) => $r->takaran * $r->bahan->harga_per_satuan);
$margin = $menu->harga_jual - $hpp;
```

HPP dan margin hanya muncul di halaman Manajer; halaman daftar menu Kasir tidak boleh mengirim data itu sama sekali (bukan hanya disembunyikan di tampilan).

### D9. Stok masuk dan batas minimum (butir 2)

- Stok masuk: validasi `jumlah > 0`; `Bahan::increment('stok', $jumlah)` + catat `stok_masuk`; panggil `MenuStatusService::segarkan`.
- Bahan menipis: `Bahan::whereColumn('stok', '<=', 'batas_minimum')`. Sama dengan atau kurang dari batas dianggap menipis. Karena batas awal 0, bahan dengan stok 0 sudah ikut menipis.
- Daftar menipis harus langsung berubah setelah Manajer mengubah batas (dihitung saat halaman dibuka, jangan di-cache).

### D10. Laporan (butir 9)

- Penjualan per menu per periode: gabungkan `detail_pesanan` lewat `pesanan.created_at` antara tanggal awal dan akhir; pakai `harga_saat_transaksi` (bukan `menu.harga_jual`); jangan hitung pesanan `Dibatalkan`.
- Bahan menipis: query di D9.
- Margin per menu: dari D8.

### D11. Validasi (ringkas)

| Input | Aturan |
|---|---|
| Nomor meja | integer, 1–10 |
| Metode bayar | `Tunai` atau `QRIS` |
| Jumlah porsi | integer, minimal 1 |
| Password | minimal 8 karakter |
| Nama bahan | `trim`, unik tanpa peduli huruf besar/kecil |
| Takaran, jumlah stok masuk | angka lebih dari 0 |

---

## E. Front End Web (Kasir dan Manajer)

Teknologi usulan: **Blade** (dirender server) + CSS (Bootstrap/Tailwind atau CSS sendiri). Tidak memerlukan REST API, sesuai aturan soal.

### E1. Kerangka

```
resources/views/
├── layouts/app.blade.php      (header "Kedai Kopi Senja", sidebar per peran, slot konten)
├── auth/login.blade.php
├── auth/ganti-password.blade.php
├── kasir/   pesanan-baru, daftar-pesanan, struk, daftar-menu, reset-password
└── manajer/ bahan, menu, resep, stok-masuk, batas-minimum, hpp-margin, laporan
```

Sidebar dibangun dari `auth()->user()->role`, dan keamanan tetap dari middleware (menyembunyikan menu bukan pengganti `403`).

### E2. Halaman (mengikuti wireframe di `Ui.pdf`)

| Peran | Halaman | Route | Isi |
|---|---|---|---|
| Semua staf | Login | `/login` | nomor HP + password; tolak peran Pelanggan di web |
| Semua staf | Ganti password | `/ganti-password` | password lama, baru, konfirmasi (min. 8) |
| Kasir | Pesanan Baru | `/pesanan/baru` | daftar menu + tombol +/−, pilih meja 1–10, metode bayar, ringkasan subtotal/service/pajak/total, tombol buat pesanan |
| Kasir | Daftar Pesanan | `/pesanan` | tabel pesanan, tombol ubah status, batal (hanya saat Baru), lihat struk |
| Kasir | Struk | `/pesanan/{id}/struk` | rincian item, subtotal, service 5%, pajak 10%, total, tombol cetak |
| Kasir | Daftar Menu | `/daftar-menu` | nama, harga, status (tanpa resep, HPP, margin) |
| Kasir | Reset Password Pelanggan | `/pelanggan/reset` | cari nomor HP → buat password sementara |
| Manajer | Kelola Bahan | `/manajer/bahan` | tambah/ubah bahan, satuan dasar |
| Manajer | Kelola Menu | `/manajer/menu` | tambah/ubah, nonaktifkan/aktifkan |
| Manajer | Kelola Resep | `/manajer/resep` | pilih menu, tambah bahan + takaran (banyak bahan) |
| Manajer | Catat Stok Masuk | `/manajer/stok-masuk` | tanggal, bahan, jumlah + riwayat |
| Manajer | Batas Minimum & Bahan Menipis | `/manajer/batas-minimum` | ubah batas per bahan; daftar menipis ikut berubah |
| Manajer | HPP & Margin | `/manajer/hpp-margin` | HPP, harga jual, margin per menu; tombol sinkron Supplier; tanggal harga diperbarui |
| Manajer | Laporan | `/manajer/laporan` | tab: penjualan per menu (filter tanggal), bahan menipis, margin per menu |

### E3. Hal yang sering terlewat

- Tampilkan pesan error dari service (misalnya bahan yang kurang) di halaman Pesanan Baru dengan jelas: nama bahan, butuh, tersedia.
- Menu berstatus `Habis`/`Nonaktif`/tanpa resep: tombol pesan dinonaktifkan **dan** ditolak di server.
- Setelah `Dibatalkan`/`Selesai`, tombol aksi disembunyikan, tetapi server tetap menolak bila dipaksa.
- Gunakan `@csrf` di setiap form, `@method('PUT'/'DELETE')` bila perlu.
- Format rupiah di satu helper (`number_format($x, 0, ',', '.')`) agar konsisten di struk dan laporan.

---

## F. APK Android (Pelanggan)

Teknologi bebas (Kotlin/Java native, Flutter, React Native, dll.). Syarat dari soal: **bukan WebView**, berjalan di Android 8.0 (API 26) atau lebih baru, mengambil data lewat REST API.

### F1. Layar dan endpoint

| Layar (wireframe) | Endpoint | Catatan |
|---|---|---|
| Daftar | `POST /api/register` | nomor HP + password (min. 8) |
| Masuk | `POST /api/login` | simpan token; bila server menandai wajib ganti password, buka layar Buat Password Baru |
| Buat Password Baru | `POST /api/ganti-password` | tampil bila `wajib_ganti_password = true` |
| Menu | `GET /api/menu` | tampilkan status Habis/Nonaktif sebagai tidak bisa dipilih |
| Pesanan Baru | `POST /api/pesanan` | pilih item, nomor meja 1–10, metode Tunai/QRIS |
| Konfirmasi / error | — | bila 422 stok kurang, tampilkan bahan yang kurang dari respons |
| Pesanan Saya | `GET /api/pesanan` | status berjenjang Baru → Diproses → Selesai |
| Struk | `GET /api/pesanan/{id}/struk` | subtotal, service, pajak, total |
| Akun | — | keluar, ganti password |
| Ganti Password | `POST /api/ganti-password` | password lama + baru |
| Logout | `POST /api/logout` | hapus token di server **dan** di HP |

Semua permintaan setelah login memakai header:

```
Authorization: Bearer <token>
Accept: application/json
```

### F2. Alamat server

- **Jangan** memakai `localhost` atau `127.0.0.1` di HP. Pakai IP komputer (contoh `http://192.168.1.10:8000`).
- Emulator Android memakai `http://10.0.2.2:8000`.
- Sediakan alamat server sebagai satu konstanta/pengaturan, mudah diganti saat demo.
- Android 9 ke atas memblokir HTTP biasa (cleartext). Karena server lokal memakai `http://`, izinkan di manifest atau `network_security_config`:

```xml
<application android:usesCleartextTraffic="true" ... >
```

- Hapus (atau rapikan) izin berlebih dan pastikan `minSdk = 26`.
- Simpan APK hasil build ke folder `/apk` di repo, lalu commit.

### F3. Uji APK sebelum wawancara

1. HP dan komputer di Wi-Fi yang sama, firewall port 8000 dibuka (B7).
2. Daftar → login → pesan → lihat status → lihat struk.
3. Coba buka pesanan orang lain (harus 403 / gagal tampil).
4. Reset password lewat Kasir di web, lalu login ulang di APK dan pastikan dipaksa ganti password.
5. Coba pesan lagi saat masih ada pesanan Baru (harus ditolak).

---

## G. CLI — Impor CSV dan Seeder

### G1. Letak berkas

```
database/data/bahan.csv  menu.csv  resep.csv     (salin APA ADANYA, jangan dirapikan manual)
app/Console/Commands/ImporDataAwal.php           (php artisan impor:data-awal)
database/seeders/StafSeeder.php
database/seeders/DemoSeeder.php
```

```powershell
mkdir database\data
copy C:\lokasi\file\bahan.csv database\data\
copy C:\lokasi\file\menu.csv  database\data\
copy C:\lokasi\file\resep.csv database\data\
```

### G2. `impor:data-awal` (butir 10)

Urutan baca: `bahan.csv` → `menu.csv` → `resep.csv`.

| Masalah data | Penanganan |
|---|---|
| Satuan tidak seragam (`kg`, `g`, `liter`, `ml`, `pcs`) | Ubah ke satuan dasar: kg → g (×1000), liter → ml (×1000). **Harga per satuan juga disesuaikan** (harga per kg ÷ 1000 = harga per g) |
| Nama bahan ganda / beda huruf besar-kecil / spasi | `trim` + banding tanpa peduli huruf besar-kecil; duplikat ditolak dan dicatat |
| Nama bahan di `resep.csv` salah ketik | Cocokkan ke bahan terdekat (usulan: `similar_text` ≥ 80%) |
| Bahan di `resep.csv` tidak ada sama sekali di `bahan.csv` | Lewati barisnya, catat di log |
| Batas minimum awal | Set `0` untuk semua bahan |

Setiap penolakan ditulis ke log (`Log::warning(...)`) dengan nomor baris dan alasan, lalu ringkasannya dicetak di terminal (diterima/ditolak). Tulis metode pencocokan di README ("Keputusan Anda").

Perintah harus aman dijalankan dalam database yang sudah `migrate:fresh`. Bila dijalankan dua kali tanpa reset, hindari data ganda (misalnya `updateOrCreate` berdasarkan nama).

### G3. Seeder

- `StafSeeder`: satu Manajer dan satu Kasir, password di-hash.
- `DemoSeeder`: **hanya** mengubah stok Susu UHT menjadi 300 ml (0,3 liter). Pakai `update` (bukan membuat baris baru) agar aman diulang, dan jangan membuat pesanan apa pun.

```php
Bahan::where('nama_bahan', 'Susu UHT')->update(['stok' => 300]);   // 300 ml = 0,3 liter
```

Akun uji (usulan; samakan dengan seeder dan README):

| Peran | Login | Password |
|---|---|---|
| Manajer | `081100000001` | `Manajer123` |
| Kasir | `081100000002` | `Kasir123` |
| Pelanggan | `081100000003` (daftar lewat APK) | `Pelanggan123` |

---

## H. Git dan pengumpulan

Syarat soal: repo **privat** sejak dibuat, dibuat pada/setelah hari 1, ada commit di sedikitnya **2 dari 3 hari**, akses diberikan ke akun penguji.

```powershell
git status
git add database/migrations
git commit -m "Tambah migrasi tabel inti"
git add app/Console database/data
git commit -m "Tambah perintah impor data awal CSV"
git add database/seeders
git commit -m "Tambah seeder staf dan data demo"
git add app/Http app/Services routes
git commit -m "Tambah alur pesanan, service, dan hak akses peran"
git push
```

Pastikan APK ikut ter-commit:

```powershell
git check-ignore -v apk/NAMA_APK.apk     # tidak boleh ada keluaran
git add apk/
git commit -m "Tambah APK"
git push
```

Jangan commit `.env` (isinya API key). Sediakan `.env.example` dengan `SUPPLIER_URL=` dan `SUPPLIER_API_KEY=` kosong.

### Struktur repo yang dikumpulkan

```
/app, /database, /resources, /routes ...   kode Laravel
/apk/NAMA_APK.apk
/docs/erd/        gambar ERD
/docs/uml/        class diagram, use case, activity diagram (+ use case scenario)
README.md         ikuti 10 bagian Lampiran B (ringkasan, teknologi, cara jalan server,
                  cara pasang APK, akun uji, letak berkas, keputusan, self-check 14 butir,
                  belum selesai, referensi dan bantuan)
```

---

## I. Peta 14 butir penerimaan

| Butir | Topik | Bagian dokumen ini |
|---|---|---|
| 1 | Kelola bahan, menu, resep | C1, D2, E2 (Kelola Bahan/Menu/Resep) |
| 2 | Stok masuk, batas minimum, bahan menipis | D9, E2 |
| 3 | Pesanan multi-item, stok berkurang, menu tanpa resep/nonaktif ditolak | D6 langkah 2–5 |
| 4 | Stok kurang ditolak, kebutuhan gabungan (**inti**) | D6 langkah 3–4 |
| 5 | Menu otomatis Habis | D7 |
| 6 | Status berjenjang, batal hanya Baru + Kasir, stok kembali | D6 (ubah status, batal) |
| 7 | Struk service 5%, pajak 10%, struk lama tetap (**inti**) | D6 langkah 6, C3 (`harga_saat_transaksi`) |
| 8 | HPP dan margin | D8 |
| 9 | Laporan + hak akses per peran | D4, D10, E2 |
| 10 | Impor CSV | G2 |
| 11 | Supplier + fallback | D8 |
| 12 | Akun pelanggan (APK) | D5, F1 |
| 13 | Pesan lewat APK, satu pesanan Baru | D6 langkah 0, F1 |
| 14 | Pantau status dan struk sendiri | D4 (cek pemilik), F1 |

---

## J. Masalah yang sering muncul

| Masalah | Penyebab | Perbaikan |
|---|---|---|
| `php` / `mysql` tidak dikenali | PATH XAMPP belum diset | Tambah `C:\xampp\php` dan `C:\xampp\mysql\bin` ke PATH, buka ulang terminal |
| `SQLSTATE[HY000] [1049] Unknown database` | Database belum dibuat | Buat database di phpMyAdmin atau `CREATE DATABASE` |
| `SQLSTATE[HY000] [2002]` | MySQL XAMPP belum Start atau `DB_HOST` salah | Start MySQL di XAMPP, cek `.env`, `php artisan config:clear` |
| Perubahan `.env` tidak terbaca | Cache konfigurasi | `php artisan config:clear` |
| Migrasi gagal karena foreign key | Urutan migrasi salah | Urutkan nama file migrasi (B3) |
| `Target class [peran] does not exist` | Alias middleware belum didaftarkan | Lihat Bagian A |
| `419 Page Expired` pada form web | `@csrf` hilang | Tambahkan `@csrf` |
| `401`/`403` pada API | Token/`Accept: application/json` tidak dikirim, atau peran salah | Kirim kedua header (F1) |
| APK tidak bisa konek | Pakai `localhost`, beda Wi-Fi, firewall, atau cleartext diblokir | F2 dan B7 |
| `curl` di PowerShell aneh | `curl` adalah alias `Invoke-WebRequest` | Pakai `curl.exe` |
| Impor menolak baris | Data klien memang kotor | Itu benar; cek `storage\logs\laravel.log` |
| Supplier error saat demo | Alamat/API key salah (memang diuji) | Pastikan masuk `catch`, harga terakhir + tanggal tampil |
