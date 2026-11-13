<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE users CHANGE name nama VARCHAR(255) NOT NULL');

        Schema::table('users', function (Blueprint $table) {
            $table->string('no_hp', 20)->unique();
            $table->enum('role', ['Kasir', 'Manajer', 'Pelanggan'])->default('Pelanggan');
            $table->boolean('wajib_ganti_password')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['no_hp']);
            $table->dropColumn(['no_hp', 'role', 'wajib_ganti_password']);
        });

        DB::statement('ALTER TABLE users CHANGE nama name VARCHAR(255) NOT NULL');
    }
};
