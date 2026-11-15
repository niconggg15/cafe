<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WebLoginTest extends TestCase
{
    public function test_guest_can_open_the_staff_login_page(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Masuk sebagai Kasir atau Manajer')
            ->assertSee('name="no_hp"', false)
            ->assertSee('name="password"', false);
    }

    public function test_guest_is_redirected_to_login_from_the_dashboard(): void
    {
        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }

    public function test_login_requires_phone_number_and_password(): void
    {
        $this->from(route('login'))
            ->post(route('login'), [])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors(['no_hp', 'password']);
    }

    public function test_login_uses_phone_number_and_shows_the_matching_error(): void
    {
        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('no_hp')->unique();
            $table->string('email')->nullable();
            $table->string('password');
            $table->string('role');
            $table->boolean('wajib_ganti_password')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        User::create([
            'nama' => 'Kasir Uji',
            'no_hp' => '08123456789',
            'email' => 'kasir@example.test',
            'password' => Hash::make('correct-password'),
            'role' => 'Kasir',
        ]);

        $this->from(route('login'))
            ->post(route('login'), [
                'no_hp' => '08123456789',
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'no_hp' => 'Nomor HP atau password tidak sesuai.',
            ])
            ->assertSessionHas('_old_input.no_hp', '08123456789');

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Nomor HP atau password tidak sesuai.');

        $this->post(route('login'), [
            'no_hp' => '08123456789',
            'password' => 'correct-password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertRedirect(route('kasir.pesanan.baru'));
    }

    public function test_authenticated_staff_can_open_the_dashboard_and_log_out(): void
    {
        $user = new User([
            'nama' => 'Kasir Uji',
            'role' => 'Kasir',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('kasir.pesanan.baru'));

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->get(route('dashboard'))
            ->assertRedirect(route('login'));
    }
}
