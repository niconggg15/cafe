<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DomainTableRenameTest extends TestCase
{
    public function test_domain_table_rename_preserves_rows_and_can_be_reversed(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $tableNames = [
            'detail_pesanans',
            'stok_masuks',
            'reseps',
            'pesanans',
            'menus',
            'bahans',
        ];

        foreach ($tableNames as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('marker')->default('preserved');
            });
            DB::table($tableName)->insert(['marker' => 'preserved']);
        }

        $migration = require database_path('migrations/2026_11_14_000001_rename_domain_tables_to_singular.php');
        $migration->up();

        foreach (['detail_pesanan', 'stok_masuk', 'resep', 'pesanan', 'menu', 'bahan'] as $tableName) {
            $this->assertTrue(Schema::hasTable($tableName));
            $this->assertDatabaseHas($tableName, ['marker' => 'preserved']);
        }

        $migration->down();

        foreach ($tableNames as $tableName) {
            $this->assertTrue(Schema::hasTable($tableName));
        }
    }
}
