# ERD Kedai Kopi Senja

ERD ini dipetakan dari migration yang ada di repo, termasuk rename tabel domain ke nama singular.

```mermaid
erDiagram
    USERS {
        bigint id PK
        string nama
        string username UK "nullable"
        string email UK "nullable"
        timestamp email_verified_at "nullable"
        string password
        string remember_token "nullable"
        string no_hp UK
        enum role "Kasir, Manajer, Pelanggan"
        boolean wajib_ganti_password
        timestamp created_at
        timestamp updated_at
    }

    BAHAN {
        bigint id PK
        string nama_bahan UK
        enum satuan "g, ml, pcs"
        decimal stok "12,3; default 0"
        decimal batas_minimum "12,3; default 0"
        decimal harga_per_satuan "12,2; default 0"
        datetime harga_diperbarui_at "nullable"
        timestamp created_at
        timestamp updated_at
    }

    MENU {
        bigint id PK
        string nama_menu
        decimal harga_jual "12,2"
        enum status "Aktif, Habis, Nonaktif"
        enum kategori "Kopi, Non-kopi, Makanan"
        timestamp created_at
        timestamp updated_at
    }

    RESEP {
        bigint id PK
        bigint menu_id FK
        bigint bahan_id FK
        decimal takaran "12,3"
        timestamp created_at
        timestamp updated_at
    }

    STOK_MASUK {
        bigint id PK
        bigint bahan_id FK
        bigint user_id FK
        date tanggal
        decimal jumlah "12,3"
        timestamp created_at
        timestamp updated_at
    }

    PESANAN {
        bigint id PK
        bigint pelanggan_id FK "nullable"
        bigint kasir_id FK "nullable"
        tinyint no_meja "unsigned; 1-10"
        enum metode_bayar "Tunai, QRIS"
        enum status "Baru, Diproses, Selesai, Dibatalkan"
        decimal subtotal "12,2"
        decimal service_charge "12,2"
        decimal pajak "12,2"
        decimal total_bayar "12,2"
        timestamp created_at
        timestamp updated_at
    }

    DETAIL_PESANAN {
        bigint id PK
        bigint pesanan_id FK
        bigint menu_id FK
        int jumlah "unsigned"
        decimal harga_saat_transaksi "12,2"
        string nama_menu
        json resep_saat_transaksi "nullable"
        timestamp created_at
        timestamp updated_at
    }

    USERS o|--o{ PESANAN : "pelanggan_id"
    USERS o|--o{ PESANAN : "kasir_id"
    USERS ||--o{ STOK_MASUK : "user_id"
    BAHAN ||--o{ RESEP : "bahan_id"
    MENU ||--o{ RESEP : "menu_id"
    BAHAN ||--o{ STOK_MASUK : "bahan_id"
    PESANAN ||--o{ DETAIL_PESANAN : "pesanan_id"
    MENU ||--o{ DETAIL_PESANAN : "menu_id"
```

Constraint dan perilaku relasi yang didefinisikan migration:

- `users.no_hp` dan `bahan.nama_bahan` unik; `users.email` nullable dan memiliki unique index dari migration awal.
- Pasangan `resep.menu_id` + `resep.bahan_id` unik; takaran resep dan jumlah stok masuk harus positif.
- Stok, batas minimum, harga, subtotal, biaya, dan total dibatasi agar tidak negatif; nomor meja dibatasi 1–10.
- `resep.menu_id` menghapus resep terkait saat menu dihapus; bahan yang masih dipakai resep dan menu yang sudah tercatat pada detail pesanan tidak dapat dihapus.
- Detail pesanan dihapus saat pesanan dihapus. Relasi `pesanan.pelanggan_id` dan `pesanan.kasir_id` menjadi `NULL` saat user terkait dihapus; stok masuk membatasi penghapusan user/bahan yang dirujuk.
