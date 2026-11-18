<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class PelangganSeeder extends Seeder
{
    public function run()
    {
        $pelanggan = User::firstOrCreate(
            ['no_hp' => '081234567890'],
            [
                'nama' => 'Pelanggan Demo',
                'email' => 'pelanggan.demo@kedai.local',
                'password' => Hash::make('Pelanggan123'),
                'role' => 'Pelanggan',
                'wajib_ganti_password' => false,
            ],
        );

        if ($pelanggan->role !== 'Pelanggan') {
            throw new RuntimeException('Nomor HP pelanggan demo sudah digunakan oleh akun non-pelanggan.');
        }
    }
}
