<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement('ALTER TABLE users MODIFY no_hp VARCHAR(20) NULL');

        DB::table('users')
            ->where('role', 'Manajer')
            ->where(function ($query) {
                $query->whereNull('username')->orWhere('username', '');
            })
            ->update(['username' => 'manajer']);

        DB::table('users')
            ->where('role', 'Kasir')
            ->where(function ($query) {
                $query->whereNull('username')->orWhere('username', '');
            })
            ->update(['username' => 'kasir']);

        DB::table('users')
            ->whereIn('role', ['Manajer', 'Kasir'])
            ->update(['no_hp' => null]);
    }

    public function down()
    {
        if (DB::table('users')->whereNull('no_hp')->exists()) {
            throw new RuntimeException('Cannot require users.no_hp while null phone numbers exist.');
        }

        DB::statement('ALTER TABLE users MODIFY no_hp VARCHAR(20) NOT NULL');
    }
};
