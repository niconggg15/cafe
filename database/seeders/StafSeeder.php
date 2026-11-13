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
        User::firstOrCreate(
            ['no_hp' => '081100000001'],
            [
                'nama' => 'Manajer',
                'email' => '081100000001@kedai.local',
                'password' => Hash::make('Manajer123'),
                'role' => 'Manajer',
                'wajib_ganti_password' => true,
            ]
        );

        User::firstOrCreate(
            ['no_hp' => '081100000002'],
            [
                'nama' => 'Kasir',
                'email' => '081100000002@kedai.local',
                'password' => Hash::make('Kasir123'),
                'role' => 'Kasir',
                'wajib_ganti_password' => true,
            ]
        );
    }
}
