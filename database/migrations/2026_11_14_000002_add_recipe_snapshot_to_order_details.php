<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('detail_pesanan', function (Blueprint $table) {
            $table->json('resep_saat_transaksi')->nullable();
        });
    }

    public function down()
    {
        Schema::table('detail_pesanan', function (Blueprint $table) {
            $table->dropColumn('resep_saat_transaksi');
        });
    }
};
