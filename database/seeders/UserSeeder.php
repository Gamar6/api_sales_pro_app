<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('users')->insert([
            [
                'name' => 'Admin Fiva Food',
                'username' => 'admin',
                'email' => 'admin@fivafood.com',
                'nohp' => '081234567890',
                'password' => Hash::Make('password'),
                'role' => 'admin',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Budi Sales 1',
                'username' => 'sales1',
                'email' => 'sales1@fivafood.com',
                'nohp' => '081298765432',
                'password' => Hash::make('password'),
                'role' => 'sales',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Andi Sales 2',
                'username' => 'sales2',
                'email' => 'sales2@fivafood.com',
                'nohp' => '081311223344',
                'password' => Hash::make('password'),
                'role' => 'sales',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}