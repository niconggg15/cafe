<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('detail_pesanans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pesanan_id')->constrained('pesanans')->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained('menus')->restrictOnDelete();
            $table->unsignedInteger('jumlah');
            $table->decimal('harga_saat_transaksi', 12, 2);
            $table->string('nama_menu');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE detail_pesanans ADD CONSTRAINT detail_pesanans_jumlah_positive CHECK (jumlah > 0), ADD CONSTRAINT detail_pesanans_harga_nonnegative CHECK (harga_saat_transaksi >= 0)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detail_pesanans');
    }
};
