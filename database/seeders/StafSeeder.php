<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class StafSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['username' => 'manajer'],
            [
                'nama' => 'Manajer',
                'no_hp' => null,
                'email' => '081100000001@kedai.local',
                'password' => Hash::make('Manajer123'),
                'role' => 'Manajer',
                'wajib_ganti_password' => false,
            ]
        );

        User::updateOrCreate(
            ['username' => 'kasir'],
            [
                'nama' => 'Kasir',
                'no_hp' => null,
                'email' => '081100000002@kedai.local',
                'password' => Hash::make('Kasir123'),
                'role' => 'Kasir',
                'wajib_ganti_password' => false,
            ]
        );
    }
}
