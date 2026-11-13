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
        Schema::create('pesanans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pelanggan_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('kasir_id')->nullable()->constrained('users')->nullOnDelete();
            $table->unsignedTinyInteger('no_meja');
            $table->enum('metode_bayar', ['Tunai', 'QRIS']);
            $table->enum('status', ['Baru', 'Diproses', 'Selesai', 'Batal'])->default('Baru');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('service_charge', 12, 2)->default(0);
            $table->decimal('pajak', 12, 2)->default(0);
            $table->decimal('total_bayar', 12, 2)->default(0);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE pesanans ADD CONSTRAINT pesanans_no_meja_valid CHECK (no_meja BETWEEN 1 AND 10), ADD CONSTRAINT pesanans_nominal_nonnegative CHECK (subtotal >= 0 AND service_charge >= 0 AND pajak >= 0 AND total_bayar >= 0)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pesanans');
    }
};
