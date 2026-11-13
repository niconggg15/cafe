<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NULL');

        DB::statement("ALTER TABLE pesanans MODIFY status ENUM('Baru', 'Diproses', 'Selesai', 'Batal', 'Dibatalkan') NOT NULL DEFAULT 'Baru'");
        DB::table('pesanans')->where('status', 'Batal')->update(['status' => 'Dibatalkan']);
        DB::statement("ALTER TABLE pesanans MODIFY status ENUM('Baru', 'Diproses', 'Selesai', 'Dibatalkan') NOT NULL DEFAULT 'Baru'");
    }

    public function down()
    {
        if (DB::table('users')->whereNull('email')->exists()) {
            throw new RuntimeException('Cannot require users.email while null email addresses exist.');
        }

        DB::statement('ALTER TABLE users MODIFY email VARCHAR(255) NOT NULL');

        DB::statement("ALTER TABLE pesanans MODIFY status ENUM('Baru', 'Diproses', 'Selesai', 'Batal', 'Dibatalkan') NOT NULL DEFAULT 'Baru'");
        DB::table('pesanans')->where('status', 'Dibatalkan')->update(['status' => 'Batal']);
        DB::statement("ALTER TABLE pesanans MODIFY status ENUM('Baru', 'Diproses', 'Selesai', 'Batal') NOT NULL DEFAULT 'Baru'");
    }
};
