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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('nama_menu');
            $table->decimal('harga_jual', 12, 2);
            $table->enum('status', ['Aktif', 'Habis', 'Nonaktif'])->default('Aktif');
            $table->timestamps();
        });

        DB::statement('ALTER TABLE menus ADD CONSTRAINT menus_harga_nonnegative CHECK (harga_jual >= 0)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('menus');
    }
};
