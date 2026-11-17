<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Pengaturan kategori menu hanya mendukung MySQL.');
        }

        DB::statement("ALTER TABLE menu MODIFY kategori VARCHAR(255) NOT NULL DEFAULT 'Minuman'");
    }

    public function down()
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            throw new RuntimeException('Pengaturan kategori menu hanya mendukung MySQL.');
        }

        DB::statement("ALTER TABLE menu MODIFY kategori VARCHAR(255) NOT NULL DEFAULT 'Kopi'");
    }
};
