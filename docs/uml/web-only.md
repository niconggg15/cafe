# UML Implementasi Web Kedai Kopi Senja

Dokumen ini menggambarkan fitur yang benar-benar tersedia pada web Kasir dan Manajer. Aplikasi pelanggan, pemesanan pelanggan, dan REST API pelanggan tidak termasuk lingkup implementasi ini. Diagram raster di folder ini merupakan materi rancangan awal dan masih dapat menampilkan alur APK.

## Use case web

```mermaid
flowchart LR
    kasir[Kasir]
    manajer[Manajer]
    supplier[Layanan Supplier API]

    subgraph web[Web Kedai Kopi Senja]
        login[Login dan logout]
        password[Ganti password]
        order[Buat pesanan]
        list[Lihat daftar pesanan dan struk]
        status[Ubah status pesanan]
        cancel[Batalkan pesanan Baru]
        menuKasir[Lihat daftar menu]
        reset[Reset password pelanggan]
        bahan[Kelola bahan]
        menu[Kelola menu dan status]
        recipe[Kelola resep]
        stock[Catat stok masuk]
        minimum[Atur batas minimum dan lihat bahan menipis]
        hpp[Lihat HPP dan margin]
        report[Lihat laporan]
        sync[Sinkronkan harga supplier]
    end

    kasir --> login
    kasir --> password
    kasir --> order
    kasir --> list
    kasir --> status
    kasir --> cancel
    kasir --> menuKasir
    kasir --> reset

    manajer --> login
    manajer --> password
    manajer --> bahan
    manajer --> menu
    manajer --> recipe
    manajer --> stock
    manajer --> minimum
    manajer --> hpp
    manajer --> report
    manajer --> sync
    sync <--> supplier
```

## Class dan relasi domain

```mermaid
classDiagram
    class User {
        id
        nama
        username
        no_hp
        role
        wajib_ganti_password
    }
    class Bahan {
        id
        nama_bahan
        satuan
        stok
        batas_minimum
        harga_per_satuan
        harga_diperbarui_at
    }
    class Menu {
        id
        nama_menu
        kategori
        harga_jual
        status
    }
    class Resep {
        id
        menu_id
        bahan_id
        takaran
    }
    class StokMasuk {
        id
        bahan_id
        user_id
        tanggal
        jumlah
    }
    class Pesanan {
        id
        kasir_id
        no_meja
        metode_bayar
        status
        subtotal
        service_charge
        pajak
        total_bayar
    }
    class DetailPesanan {
        id
        pesanan_id
        menu_id
        jumlah
        harga_saat_transaksi
        nama_menu
        resep_saat_transaksi
    }
    class PesananService
    class MenuStatusService
    class HppService
    class SupplierService

    Menu "1" --> "0..*" Resep
    Bahan "1" --> "0..*" Resep
    Bahan "1" --> "0..*" StokMasuk
    User "1" --> "0..*" StokMasuk : dicatat oleh
    User "1" --> "0..*" Pesanan : kasir_id
    Pesanan "1" --> "1..*" DetailPesanan
    Menu "1" --> "0..*" DetailPesanan
    PesananService ..> Pesanan
    PesananService ..> MenuStatusService
    HppService ..> Resep
    SupplierService ..> Bahan
```

Catatan:

- Akun pelanggan demo tersimpan sebagai `User` berperan `Pelanggan` agar Kasir dapat mencari dan mereset password-nya. Pesanan web tidak berelasi dengan pelanggan.
- Beberapa pesanan boleh menggunakan nomor meja yang sama; pembatasan meja hanya 1–10.
- Pembatalan pesanan `Baru` oleh Kasir mengembalikan stok menggunakan resep terkini, sesuai keputusan yang dicatat di README.
