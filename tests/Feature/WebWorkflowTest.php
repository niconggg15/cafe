<?php

namespace Tests\Feature;

use App\Models\Bahan;
use App\Models\Menu;
use App\Models\Pesanan;
use App\Models\Resep;
use App\Models\User;
use App\Services\PesananService;
use App\Services\SupplierService;
use Database\Seeders\DemoSeeder;
use Database\Seeders\KategoriMenuSeeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Client\Request as ClientRequest;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WebWorkflowTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('bahan', function (Blueprint $table) {
            $table->id();
            $table->string('nama_bahan');
            $table->string('satuan');
            $table->decimal('stok', 12, 3)->default(0);
            $table->decimal('batas_minimum', 12, 3)->default(0);
            $table->decimal('harga_per_satuan', 12, 2)->default(0);
            $table->dateTime('harga_diperbarui_at')->nullable();
            $table->string('status')->default('Aktif');
            $table->timestamps();
        });
        Schema::create('menu', function (Blueprint $table) {
            $table->id();
            $table->string('nama_menu');
            $table->string('kategori')->default('Kopi');
            $table->decimal('harga_jual', 12, 2);
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('kategori_menu', function (Blueprint $table) {
            $table->id();
            $table->string('kode_kategori')->unique();
            $table->string('nama_kategori')->unique();
            $table->timestamps();
        });
        Schema::create('resep', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('menu_id');
            $table->unsignedBigInteger('bahan_id');
            $table->decimal('takaran', 12, 3);
            $table->timestamps();
        });
        Schema::create('pesanan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kasir_id')->nullable();
            $table->unsignedTinyInteger('no_meja');
            $table->string('metode_bayar');
            $table->string('status');
            $table->decimal('subtotal', 12, 2);
            $table->decimal('service_charge', 12, 2);
            $table->decimal('pajak', 12, 2);
            $table->decimal('total_bayar', 12, 2);
            $table->timestamps();
        });
        Schema::create('detail_pesanan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pesanan_id');
            $table->unsignedBigInteger('menu_id');
            $table->unsignedInteger('jumlah');
            $table->decimal('harga_saat_transaksi', 12, 2);
            $table->string('nama_menu');
            $table->json('resep_saat_transaksi')->nullable();
            $table->timestamps();
        });
        Schema::create('stok_masuk', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bahan_id');
            $table->unsignedBigInteger('user_id');
            $table->date('tanggal');
            $table->decimal('jumlah', 12, 3);
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('username')->nullable()->unique();
            $table->string('no_hp')->nullable()->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('role');
            $table->boolean('wajib_ganti_password')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function test_order_deducts_stock_and_cancellation_restores_current_recipe(): void
    {
        $bahan = Bahan::create([
            'nama_bahan' => 'Biji kopi',
            'satuan' => 'g',
            'stok' => 500,
            'harga_per_satuan' => 100,
        ]);
        $menu = Menu::create(['nama_menu' => 'Kopi Senja', 'harga_jual' => 20000, 'status' => 'Aktif']);
        Resep::create(['menu_id' => $menu->id, 'bahan_id' => $bahan->id, 'takaran' => 10]);

        $service = app(PesananService::class);
        $pesanan = $service->buat([
            'items' => [$menu->id => 2],
            'no_meja' => 3,
            'metode_bayar' => 'Tunai',
        ], 9);

        $this->assertSame(480.0, (float) $bahan->fresh()->stok);
        $this->assertSame(2000.0, (float) $pesanan->service_charge);
        $this->assertSame(4200.0, (float) $pesanan->pajak);
        $this->assertSame(46200.0, (float) $pesanan->total_bayar);
        $service->batalkan($pesanan);

        $this->assertSame(500.0, (float) $bahan->fresh()->stok);
        $this->assertSame('Dibatalkan', $pesanan->fresh()->status);
    }

    public function test_csv_import_converts_units_matches_recipe_typo_and_is_idempotent(): void
    {
        $this->seed(KategoriMenuSeeder::class);
        $this->artisan('impor:data-awal')->assertExitCode(0);

        $this->assertSame(20, Bahan::count());
        $this->assertSame(14, Menu::count());
        $this->assertSame(63, Resep::count());
        $this->assertSame('Minuman', Menu::where('nama_menu', 'Espresso')->value('kategori'));
        $this->assertSame(5000.0, (float) Bahan::where('nama_bahan', 'Biji kopi arabika')->value('stok'));
        $this->assertSame(180.0, (float) Bahan::where('nama_bahan', 'Biji kopi arabika')->value('harga_per_satuan'));

        $flatWhite = Menu::where('nama_menu', 'Flat White')->firstOrFail();
        $arabika = Bahan::where('nama_bahan', 'Biji kopi arabika')->firstOrFail();
        $this->assertSame(18.0, (float) Resep::where('menu_id', $flatWhite->id)->where('bahan_id', $arabika->id)->value('takaran'));
        $this->assertSame(4, Resep::where('menu_id', Menu::where('nama_menu', 'Latte')->value('id'))->count());
        $this->assertSame(6, Resep::where('menu_id', Menu::where('nama_menu', 'Mocha')->value('id'))->count());

        $this->seed(DemoSeeder::class);
        $this->assertSame(300.0, (float) Bahan::where('nama_bahan', 'Susu UHT')->value('stok'));

        $this->artisan('impor:data-awal')->assertExitCode(0);
        $this->assertSame(20, Bahan::count());
        $this->assertSame(14, Menu::count());
        $this->assertSame(63, Resep::count());
    }

    public function test_cancellation_restores_stock_using_the_current_recipe(): void
    {
        $bahan = Bahan::create([
            'nama_bahan' => 'Biji kopi',
            'satuan' => 'g',
            'stok' => 500,
            'harga_per_satuan' => 100,
        ]);
        $menu = Menu::create(['nama_menu' => 'Kopi Senja', 'harga_jual' => 20000, 'status' => 'Aktif']);
        Resep::create(['menu_id' => $menu->id, 'bahan_id' => $bahan->id, 'takaran' => 10]);

        $service = app(PesananService::class);
        $pesanan = $service->buat([
            'items' => [$menu->id => 2],
            'no_meja' => 3,
            'metode_bayar' => 'Tunai',
        ], 9);

        Resep::where('menu_id', $menu->id)->update(['takaran' => 20]);
        $service->batalkan($pesanan);

        $this->assertSame(520.0, (float) $bahan->fresh()->stok);
        $this->assertSame('Dibatalkan', $pesanan->fresh()->status);
    }

    public function test_order_is_rejected_without_deducting_insufficient_stock(): void
    {
        $bahan = Bahan::create(['nama_bahan' => 'Susu', 'satuan' => 'ml', 'stok' => 15]);
        $menu = Menu::create(['nama_menu' => 'Latte', 'harga_jual' => 25000, 'status' => 'Aktif']);
        Resep::create(['menu_id' => $menu->id, 'bahan_id' => $bahan->id, 'takaran' => 10]);

        try {
            app(PesananService::class)->buat([
                'items' => [$menu->id => 2],
                'no_meja' => 3,
                'metode_bayar' => 'QRIS',
            ], 9);
            $this->fail('The order should be rejected when stock is insufficient.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('items', $exception->errors());
        }

        $this->assertSame(15.0, (float) $bahan->fresh()->stok);
        $this->assertSame(0, Pesanan::count());
    }

    public function test_order_aggregates_shared_ingredient_requirements_across_menu_items(): void
    {
        $bahan = Bahan::create(['nama_bahan' => 'Biji kopi', 'satuan' => 'g', 'stok' => 15]);
        $kopi = Menu::create(['nama_menu' => 'Kopi', 'harga_jual' => 18000, 'status' => 'Aktif']);
        $latte = Menu::create(['nama_menu' => 'Latte', 'harga_jual' => 25000, 'status' => 'Aktif']);
        Resep::create(['menu_id' => $kopi->id, 'bahan_id' => $bahan->id, 'takaran' => 10]);
        Resep::create(['menu_id' => $latte->id, 'bahan_id' => $bahan->id, 'takaran' => 10]);

        try {
            app(PesananService::class)->buat([
                'items' => [$kopi->id => 1, $latte->id => 1],
                'no_meja' => 2,
                'metode_bayar' => 'Tunai',
            ], 9);
            $this->fail('The order should aggregate shared ingredient requirements.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('Biji kopi', $exception->errors()['items'][0]);
            $this->assertStringContainsString('20,000 g', $exception->errors()['items'][0]);
        }

        $this->assertSame(15.0, (float) $bahan->fresh()->stok);
        $this->assertSame(0, Pesanan::count());
    }

    public function test_staff_routes_are_restricted_by_role(): void
    {
        $kasir = new User(['nama' => 'Kasir', 'role' => 'Kasir']);
        $manajer = new User(['nama' => 'Manajer', 'role' => 'Manajer']);

        $this->actingAs($kasir)->get(route('manajer.bahan'))->assertForbidden();
        $this->actingAs($manajer)->get(route('kasir.pesanan.baru'))->assertForbidden();
    }

    public function test_cashier_and_manager_screens_render_with_live_laravel_data(): void
    {
        $kasir = User::create([
            'nama' => 'Kasir Uji',
            'username' => 'kasir-uji',
            'password' => Hash::make('Password123'),
            'role' => 'Kasir',
        ]);
        $manajer = new User(['nama' => 'Manajer Uji', 'role' => 'Manajer']);
        $bahan = Bahan::create(['nama_bahan' => 'Biji kopi', 'satuan' => 'g', 'stok' => 100]);
        $menu = Menu::create(['nama_menu' => 'Kopi Senja', 'harga_jual' => 20000, 'status' => 'Aktif']);
        Resep::create(['menu_id' => $menu->id, 'bahan_id' => $bahan->id, 'takaran' => 10]);

        $this->actingAs($kasir)
            ->get(route('kasir.pesanan.baru'))
            ->assertOk()
            ->assertSee('Kopi Senja')
            ->assertSee('data-category-filter="Minuman"', false)
            ->assertSee('data-category-filter="Makanan"', false)
            ->assertDontSee('data-category-filter="Kopi"', false)
            ->assertDontSee('data-category-filter="Non-kopi"', false)
            ->assertSee('type="radio" name="metode_bayar" value="Tunai"', false)
            ->assertSee('type="radio" name="metode_bayar" value="QRIS"', false)
            ->assertDontSee('HPP');

        $this->get(route('kasir.pesanan.index'))
            ->assertOk()
            ->assertDontSee('Reset password pelanggan');

        $pesanan = app(PesananService::class)->buat([
            'items' => [$menu->id => 1],
            'no_meja' => 1,
            'metode_bayar' => 'Tunai',
        ], $kasir->id);
        $this->actingAs($kasir)
            ->get(route('kasir.struk', $pesanan))
            ->assertOk()
            ->assertSee('Struk Pesanan #0001');

        foreach ([
            'manajer.bahan',
            'manajer.menu',
            'manajer.resep',
            'manajer.stok-masuk',
            'manajer.batas-minimum',
            'manajer.hpp-margin',
            'manajer.laporan',
        ] as $routeName) {
            $this->actingAs($manajer)->get(route($routeName))->assertOk();
        }
    }

    public function test_manager_dashboard_hides_empty_low_stock_panel(): void
    {
        $manajer = new User(['nama' => 'Manajer Uji', 'role' => 'Manajer']);
        $this->actingAs($manajer)
            ->get(route('manajer.dashboard'))
            ->assertOk()
            ->assertSee('Bahan menipis')
            ->assertDontSee('<h2>Bahan menipis</h2>', false)
            ->assertDontSee('Tidak ada bahan menipis.');
    }

    public function test_saving_menu_refreshes_its_status_from_current_ingredient_stock(): void
    {
        $this->seed(KategoriMenuSeeder::class);
        $bahan = Bahan::create(['nama_bahan' => 'Biji kopi', 'satuan' => 'g', 'stok' => 5]);
        $menu = Menu::create(['nama_menu' => 'Kopi Senja', 'harga_jual' => 20000, 'status' => 'Aktif']);
        Resep::create(['menu_id' => $menu->id, 'bahan_id' => $bahan->id, 'takaran' => 10]);

        $this->actingAs(new User(['nama' => 'Manajer Uji', 'role' => 'Manajer']))
            ->put(route('manajer.menu.update', $menu), [
                'nama_menu' => 'Kopi Senja',
                'kategori' => 'Minuman',
                'harga_jual' => 20000,
                'status' => 'Aktif',
            ])
            ->assertRedirect();

        $this->assertSame('Habis', $menu->fresh()->status);
    }

    public function test_cashier_reset_password_normalizes_customer_phone(): void
    {
        $pelanggan = User::create([
            'nama' => 'Pelanggan Uji',
            'no_hp' => '081234567890',
            'password' => Hash::make('PasswordLama123'),
            'role' => 'Pelanggan',
        ]);

        $kasir = new User(['nama' => 'Kasir Uji', 'role' => 'Kasir']);
        $this->actingAs($kasir)
            ->post(route('kasir.reset-password.simpan'), [
                'no_hp' => '+62 812-3456-7890',
                'aksi' => 'cari',
            ])
            ->assertRedirect(route('kasir.reset-password'))
            ->assertSessionHas('kasir.reset_password.no_hp', '081234567890');

        $this->get(route('kasir.reset-password'))
            ->assertOk()
            ->assertSee('Akun ditemukan: 081234567890');

        $response = $this->post(route('kasir.reset-password.simpan'), [
            'no_hp' => '081234567890',
            'aksi' => 'reset',
        ]);

        $response->assertRedirect(route('kasir.reset-password'))
            ->assertSessionHas('reset_pelanggan', '081234567890')
            ->assertSessionMissing('kasir.reset_password.no_hp')
            ->assertSessionHas('password_sementara', function ($passwordSementara) use ($pelanggan) {
                return Hash::check($passwordSementara, $pelanggan->fresh()->password);
            });
        $this->assertTrue((bool) $pelanggan->fresh()->wajib_ganti_password);
    }

    public function test_cashier_reset_password_rejects_invalid_phone_number(): void
    {
        $this->actingAs(new User(['nama' => 'Kasir Uji', 'role' => 'Kasir']))
            ->post(route('kasir.reset-password.simpan'), ['no_hp' => '0812invalid', 'aksi' => 'cari'])
            ->assertSessionHasErrors('no_hp');
    }

    public function test_cashier_cannot_reset_password_without_searching_for_account(): void
    {
        User::create([
            'nama' => 'Pelanggan Uji',
            'no_hp' => '081234567890',
            'password' => Hash::make('PasswordLama123'),
            'role' => 'Pelanggan',
        ]);

        $this->actingAs(new User(['nama' => 'Kasir Uji', 'role' => 'Kasir']))
            ->post(route('kasir.reset-password.simpan'), [
                'no_hp' => '081234567890',
                'aksi' => 'reset',
            ])
            ->assertSessionHasErrors('no_hp');
    }

    public function test_supplier_prices_update_known_ingredients_only(): void
    {
        $bahan = Bahan::create([
            'nama_bahan' => 'Biji kopi',
            'satuan' => 'g',
            'stok' => 100,
            'harga_per_satuan' => 90,
        ]);
        config([
            'services.supplier.url' => 'https://supplier.example.test/prices',
            'services.supplier.api_key' => 'test-key',
            'services.supplier.timeout' => 5,
        ]);
        Http::fake([
            'supplier.example.test/prices' => Http::response([
                ['nama' => 'Biji kopi', 'harga' => 125],
                ['nama' => 'Bahan tidak dikenal', 'harga' => 200],
            ]),
        ]);

        $result = app(SupplierService::class)->sinkron();

        $this->assertTrue($result['ok']);
        $this->assertSame(125.0, (float) $bahan->fresh()->harga_per_satuan);
        $this->assertNotNull($bahan->fresh()->harga_diperbarui_at);
        Http::assertSent(fn (ClientRequest $request) => $request->hasHeader('X-API-Key', 'test-key'));
    }

    public function test_missing_supplier_configuration_keeps_last_price(): void
    {
        $bahan = Bahan::create(['nama_bahan' => 'Susu', 'satuan' => 'ml', 'stok' => 20, 'harga_per_satuan' => 42]);
        config(['services.supplier.url' => null, 'services.supplier.api_key' => null]);

        $result = app(SupplierService::class)->sinkron();

        $this->assertFalse($result['ok']);
        $this->assertSame(42.0, (float) $bahan->fresh()->harga_per_satuan);
    }

    public function test_staff_with_temporary_password_must_change_it_first(): void
    {
        $kasir = new User(['nama' => 'Kasir', 'role' => 'Kasir', 'wajib_ganti_password' => true]);

        $this->actingAs($kasir)
            ->get(route('dashboard'))
            ->assertRedirect(route('password.change'));
    }

    public function test_manager_can_add_material_and_record_stock_in(): void
    {
        $manager = User::create([
            'nama' => 'Manajer',
            'no_hp' => '081111111111',
            'password' => Hash::make('password'),
            'role' => 'Manajer',
        ]);
        $this->actingAs($manager);

        $this->post(route('manajer.bahan.simpan'), [
            'nama_bahan' => 'Susu UHT',
            'satuan' => 'ml',
            'batas_minimum' => 100,
            'harga_per_satuan' => 12,
        ])->assertRedirect();

        $bahan = Bahan::where('nama_bahan', 'Susu UHT')->firstOrFail();
        $this->assertSame(0.0, (float) $bahan->stok);
        $this->assertSame('Aktif', $bahan->status);

        $menu = Menu::create(['nama_menu' => 'Latte', 'harga_jual' => 25000, 'status' => 'Habis']);
        Resep::create(['menu_id' => $menu->id, 'bahan_id' => $bahan->id, 'takaran' => 20]);

        $this->post(route('manajer.stok-masuk.simpan'), [
            'bahan_id' => $bahan->id,
            'tanggal' => '2026-11-15',
            'jumlah' => 150,
        ])->assertRedirect();

        $this->assertSame(150.0, (float) $bahan->fresh()->stok);
        $this->assertSame('Aktif', $menu->fresh()->status);
        $this->assertDatabaseHas('stok_masuk', ['bahan_id' => $bahan->id, 'user_id' => $manager->id]);
    }

    public function test_manager_can_update_and_delete_unused_material(): void
    {
        $manager = User::create([
            'nama' => 'Manajer',
            'no_hp' => '081111111111',
            'password' => Hash::make('password'),
            'role' => 'Manajer',
        ]);
        $bahan = Bahan::create([
            'nama_bahan' => 'Susu',
            'satuan' => 'ml',
            'stok' => 0,
            'harga_per_satuan' => 10,
        ]);

        $this->actingAs($manager)
            ->get(route('manajer.bahan'))
            ->assertOk()
            ->assertSee('Ubah')
            ->assertSee('Hapus');

        $this->put(route('manajer.bahan.update', $bahan), [
            'nama_bahan' => 'Susu UHT',
            'satuan' => 'pcs',
            'batas_minimum' => 5,
            'harga_per_satuan' => 15,
        ])->assertRedirect();

        $this->assertDatabaseHas('bahan', [
            'id' => $bahan->id,
            'nama_bahan' => 'Susu UHT',
            'satuan' => 'pcs',
            'batas_minimum' => 5,
            'harga_per_satuan' => 15,
        ]);
        $this->assertNotNull($bahan->fresh()->harga_diperbarui_at);

        $this->delete(route('manajer.bahan.hapus', $bahan))->assertRedirect();
        $this->assertDatabaseMissing('bahan', ['id' => $bahan->id]);
    }

    public function test_manager_cannot_delete_or_change_unit_of_material_with_history(): void
    {
        $manager = User::create([
            'nama' => 'Manajer',
            'no_hp' => '081111111111',
            'password' => Hash::make('password'),
            'role' => 'Manajer',
        ]);
        $bahan = Bahan::create(['nama_bahan' => 'Susu', 'satuan' => 'ml', 'stok' => 50]);
        $menu = Menu::create(['nama_menu' => 'Latte', 'harga_jual' => 25000, 'status' => 'Aktif']);
        Resep::create(['menu_id' => $menu->id, 'bahan_id' => $bahan->id, 'takaran' => 10]);
        DB::table('stok_masuk')->insert([
            'bahan_id' => $bahan->id,
            'user_id' => $manager->id,
            'tanggal' => '2026-11-15',
            'jumlah' => 50,
        ]);

        $this->actingAs($manager)
            ->put(route('manajer.bahan.update', $bahan), [
                'nama_bahan' => 'Susu',
                'satuan' => 'g',
                'batas_minimum' => 0,
                'harga_per_satuan' => 0,
            ])
            ->assertSessionHasErrors('satuan');

        $this->delete(route('manajer.bahan.hapus', $bahan))
            ->assertRedirect(route('manajer.bahan'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('bahan', ['id' => $bahan->id, 'satuan' => 'ml']);
    }

    public function test_manager_can_toggle_material_status_without_changing_existing_recipe_or_stock_history(): void
    {
        $manager = User::create([
            'nama' => 'Manajer',
            'no_hp' => '081111111111',
            'password' => Hash::make('password'),
            'role' => 'Manajer',
        ]);
        $bahan = Bahan::create(['nama_bahan' => 'Susu', 'satuan' => 'ml', 'stok' => 50]);
        $menu = Menu::create(['nama_menu' => 'Latte', 'harga_jual' => 25000, 'status' => 'Aktif']);
        Resep::create(['menu_id' => $menu->id, 'bahan_id' => $bahan->id, 'takaran' => 10]);
        DB::table('stok_masuk')->insert([
            'bahan_id' => $bahan->id,
            'user_id' => $manager->id,
            'tanggal' => '2026-11-15',
            'jumlah' => 50,
        ]);

        $this->actingAs($manager)
            ->get(route('manajer.bahan'))
            ->assertOk()
            ->assertSee('Ubah')
            ->assertSee('Nonaktifkan')
            ->assertSee('Tidak dapat dihapus: ada stok atau riwayat.');

        $this->patch(route('manajer.bahan.status', $bahan))
            ->assertRedirect();

        $this->assertSame('Nonaktif', $bahan->fresh()->status);
        $this->assertDatabaseHas('resep', ['menu_id' => $menu->id, 'bahan_id' => $bahan->id]);
        $this->assertDatabaseHas('stok_masuk', ['bahan_id' => $bahan->id]);

        $this->post(route('manajer.resep.simpan'), [
            'menu_id' => $menu->id,
            'bahan' => [$bahan->id => 10],
        ])->assertRedirect();
        $this->assertDatabaseHas('resep', ['menu_id' => $menu->id, 'bahan_id' => $bahan->id]);

        $this->patch(route('manajer.bahan.status', $bahan))
            ->assertRedirect();
        $this->assertSame('Aktif', $bahan->fresh()->status);
    }

    public function test_inactive_material_cannot_be_added_to_a_new_recipe_or_stock_entry(): void
    {
        $manager = User::create([
            'nama' => 'Manajer',
            'no_hp' => '081111111111',
            'password' => Hash::make('password'),
            'role' => 'Manajer',
        ]);
        $bahan = Bahan::create(['nama_bahan' => 'Susu', 'satuan' => 'ml', 'status' => 'Nonaktif']);
        $menu = Menu::create(['nama_menu' => 'Latte', 'harga_jual' => 25000, 'status' => 'Habis']);
        $this->actingAs($manager);

        $this->get(route('manajer.menu'))
            ->assertOk()
            ->assertSee('Latte')
            ->assertDontSee('data-name="Susu"');
        $this->get(route('manajer.resep', ['menu_id' => $menu->id]))
            ->assertOk()
            ->assertSee('Tidak ada bahan aktif')
            ->assertDontSee('data-name="Susu"');
        $this->get(route('manajer.stok-masuk'))
            ->assertOk()
            ->assertSee('Tidak ada bahan aktif')
            ->assertDontSee('Susu');

        $this->from(route('manajer.resep', ['menu_id' => $menu->id]))
            ->post(route('manajer.resep.simpan'), [
                'menu_id' => $menu->id,
                'bahan' => [$bahan->id => 10],
            ])
            ->assertSessionHasErrors('bahan');
        $this->from(route('manajer.stok-masuk'))
            ->post(route('manajer.stok-masuk.simpan'), [
                'bahan_id' => $bahan->id,
                'tanggal' => '2026-11-15',
                'jumlah' => 10,
            ])
            ->assertSessionHasErrors('bahan_id');
    }

    public function test_manager_can_create_menu_and_add_its_recipe_from_separate_pages(): void
    {
        $this->seed(KategoriMenuSeeder::class);
        $manager = User::create([
            'nama' => 'Manajer',
            'no_hp' => '081111111111',
            'password' => Hash::make('password'),
            'role' => 'Manajer',
        ]);
        $bahan = Bahan::create([
            'nama_bahan' => 'Susu',
            'satuan' => 'ml',
            'stok' => 100,
        ]);
        $this->actingAs($manager);

        $this->get(route('manajer.menu'))
            ->assertOk()
            ->assertSee('<option>Minuman</option>', false)
            ->assertSee('<option>Makanan</option>', false)
            ->assertDontSee('<option>Kopi</option>', false)
            ->assertSee('Tambah menu')
            ->assertDontSee('Formula resep');

        $this->from(route('manajer.menu'))->post(route('manajer.menu.simpan'), [
            'nama_menu' => 'Menu Kategori Invalid',
            'kategori' => 'Kopi',
            'harga_jual' => 10000,
        ])->assertSessionHasErrors('kategori');

        $this->post(route('manajer.menu.simpan'), [
            'nama_menu' => 'Latte',
            'kategori' => 'Minuman',
            'harga_jual' => 25000,
        ])->assertSessionHasNoErrors();

        $menu = Menu::where('nama_menu', 'Latte')->firstOrFail();
        $menuUrl = route('manajer.menu');
        $resepUrl = route('manajer.resep', ['menu_id' => $menu->id]);
        $this->assertSame('Habis', $menu->status);

        $this->get($menuUrl)
            ->assertOk()
            ->assertSee('Latte')
            ->assertSee('Belum ada resep')
            ->assertSee($resepUrl)
            ->assertDontSee('Resep belum dibuat untuk menu ini.');
        $this->get($resepUrl)
            ->assertOk()
            ->assertSee('Resep belum dibuat untuk menu ini.');

        $this->from($resepUrl)
            ->post(route('manajer.resep.simpan'), [
                'menu_id' => $menu->id,
                'bahan' => [],
            ])
            ->assertRedirect($resepUrl)
            ->assertSessionHasErrors('bahan');

        $this->post(route('manajer.resep.simpan'), [
            'menu_id' => $menu->id,
            'bahan' => [$bahan->id => 20],
        ])->assertRedirect($resepUrl);

        $this->assertDatabaseHas('resep', [
            'menu_id' => $menu->id,
            'bahan_id' => $bahan->id,
            'takaran' => 20,
        ]);
        $this->assertSame('Aktif', $menu->fresh()->status);
        $this->get($resepUrl)
            ->assertOk()
            ->assertSee('Susu')
            ->assertDontSee('Resep belum dibuat untuk menu ini.');
        $this->get($menuUrl)->assertSee('1 bahan');
    }

    public function test_manager_can_delete_menu_without_order_history_and_its_recipe(): void
    {
        $manager = User::create([
            'nama' => 'Manajer',
            'no_hp' => '081111111111',
            'password' => Hash::make('password'),
            'role' => 'Manajer',
        ]);
        $bahan = Bahan::create(['nama_bahan' => 'Susu', 'satuan' => 'ml', 'stok' => 100]);
        $menu = Menu::create(['nama_menu' => 'Latte', 'harga_jual' => 25000, 'status' => 'Aktif']);
        Resep::create(['menu_id' => $menu->id, 'bahan_id' => $bahan->id, 'takaran' => 20]);

        $this->actingAs($manager)
            ->get(route('manajer.menu'))
            ->assertOk()
            ->assertSee('Hapus');

        $this->delete(route('manajer.menu.hapus', $menu))
            ->assertRedirect(route('manajer.menu'));

        $this->assertDatabaseMissing('menu', ['id' => $menu->id]);
        $this->assertDatabaseMissing('resep', ['menu_id' => $menu->id]);
    }

    public function test_manager_cannot_delete_menu_in_order_history(): void
    {
        $manager = User::create([
            'nama' => 'Manajer',
            'no_hp' => '081111111111',
            'password' => Hash::make('password'),
            'role' => 'Manajer',
        ]);
        $menu = Menu::create(['nama_menu' => 'Latte', 'harga_jual' => 25000, 'status' => 'Aktif']);
        $pesanan = Pesanan::create([
            'no_meja' => 1,
            'metode_bayar' => 'Tunai',
            'status' => 'Selesai',
            'subtotal' => 25000,
            'service_charge' => 1250,
            'pajak' => 2625,
            'total_bayar' => 28875,
        ]);
        DB::table('detail_pesanan')->insert([
            'pesanan_id' => $pesanan->id,
            'menu_id' => $menu->id,
            'jumlah' => 1,
            'harga_saat_transaksi' => 25000,
            'nama_menu' => 'Latte',
        ]);

        $this->actingAs($manager)
            ->delete(route('manajer.menu.hapus', $menu))
            ->assertRedirect(route('manajer.menu'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('menu', ['id' => $menu->id]);
        $this->assertDatabaseHas('detail_pesanan', ['menu_id' => $menu->id]);
    }

    public function test_manager_can_delete_whole_recipe_and_menu_becomes_habis(): void
    {
        $manager = User::create([
            'nama' => 'Manajer',
            'no_hp' => '081111111111',
            'password' => Hash::make('password'),
            'role' => 'Manajer',
        ]);
        $bahan = Bahan::create(['nama_bahan' => 'Susu', 'satuan' => 'ml', 'stok' => 100]);
        $menu = Menu::create(['nama_menu' => 'Latte', 'harga_jual' => 25000, 'status' => 'Aktif']);
        Resep::create(['menu_id' => $menu->id, 'bahan_id' => $bahan->id, 'takaran' => 20]);

        $this->actingAs($manager)
            ->delete(route('manajer.resep.hapus', $menu))
            ->assertRedirect(route('manajer.resep', ['menu_id' => $menu->id]));

        $this->assertDatabaseMissing('resep', ['menu_id' => $menu->id]);
        $this->assertSame('Habis', $menu->fresh()->status);
    }

    public function test_manager_can_save_recipe_without_stock_and_receives_availability_message(): void
    {
        $manager = User::create([
            'nama' => 'Manajer',
            'no_hp' => '081111111111',
            'password' => Hash::make('password'),
            'role' => 'Manajer',
        ]);
        $bahan = Bahan::create(['nama_bahan' => 'Susu', 'satuan' => 'ml', 'stok' => 0]);
        $menu = Menu::create(['nama_menu' => 'Latte', 'harga_jual' => 25000, 'status' => 'Habis']);

        $this->actingAs($manager)
            ->post(route('manajer.resep.simpan'), [
                'menu_id' => $menu->id,
                'bahan' => [$bahan->id => 20],
            ])
            ->assertRedirect(route('manajer.resep', ['menu_id' => $menu->id]))
            ->assertSessionHas('success', 'Resep berhasil disimpan, tetapi status menu tetap Habis karena stok bahan belum mencukupi.');

        $this->assertDatabaseHas('resep', ['menu_id' => $menu->id, 'bahan_id' => $bahan->id]);
        $this->assertSame('Habis', $menu->fresh()->status);
    }
}
