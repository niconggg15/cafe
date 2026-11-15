<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::rename('detail_pesanans', 'detail_pesanan');
        Schema::rename('stok_masuks', 'stok_masuk');
        Schema::rename('reseps', 'resep');
        Schema::rename('pesanans', 'pesanan');
        Schema::rename('menus', 'menu');
        Schema::rename('bahans', 'bahan');
    }

    public function down()
    {
        Schema::rename('detail_pesanan', 'detail_pesanans');
        Schema::rename('stok_masuk', 'stok_masuks');
        Schema::rename('resep', 'reseps');
        Schema::rename('pesanan', 'pesanans');
        Schema::rename('menu', 'menus');
        Schema::rename('bahan', 'bahans');
    }
};
