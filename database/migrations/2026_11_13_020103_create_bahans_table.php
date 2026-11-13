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
        Schema::create('bahans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bahan')->unique();
            $table->enum('satuan', ['g', 'ml', 'pcs']);
            $table->decimal('stok', 12, 3)->default(0);
            $table->decimal('batas_minimum', 12, 3)->default(0);
            $table->decimal('harga_per_satuan', 12, 2)->default(0);
            $table->dateTime('harga_diperbarui_at')->nullable();
            $table->timestamps();
        });

        DB::statement('ALTER TABLE bahans ADD CONSTRAINT bahans_stok_nonnegative CHECK (stok >= 0), ADD CONSTRAINT bahans_batas_nonnegative CHECK (batas_minimum >= 0), ADD CONSTRAINT bahans_harga_nonnegative CHECK (harga_per_satuan >= 0)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bahans');
    }
};
