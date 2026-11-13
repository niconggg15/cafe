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
        Schema::create('stok_masuks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->date('tanggal');
            $table->decimal('jumlah', 12, 3);
            $table->index(['bahan_id', 'tanggal']);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE stok_masuks ADD CONSTRAINT stok_masuks_jumlah_positive CHECK (jumlah > 0)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stok_masuks');
    }
};
