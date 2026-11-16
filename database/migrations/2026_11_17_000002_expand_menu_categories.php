<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Perluas kategori menu hanya mendukung MySQL.');
        }

        DB::statement("ALTER TABLE menu MODIFY kategori VARCHAR(255) NOT NULL DEFAULT 'Kopi'");
    }

    public function down()
    {
        if (DB::table('menu')->whereNotIn('kategori', ['Kopi', 'Non-kopi', 'Makanan'])->exists()) {
            throw new RuntimeException('Kategori menu baru masih digunakan; ubah nilainya sebelum rollback.');
        }

        DB::statement("ALTER TABLE menu MODIFY kategori ENUM('Kopi','Non-kopi','Makanan') NOT NULL DEFAULT 'Kopi'");
    }
};
