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
        Schema::create('reseps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->foreignId('bahan_id')->constrained('bahans')->restrictOnDelete();
            $table->decimal('takaran', 12, 3);
            $table->unique(['menu_id', 'bahan_id']);
            $table->timestamps();
        });

        DB::statement('ALTER TABLE reseps ADD CONSTRAINT reseps_takaran_positive CHECK (takaran > 0)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('reseps');
    }
};
