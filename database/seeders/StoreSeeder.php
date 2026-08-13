<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class StoreSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('stores')->insert([
            [
                'name' => 'Toko Sembako Jaya',
                'address' => 'Jl. Jendral Sudirman No. 12, Cilacap',
                'latitude' => -7.71830000,
                'longitude' => 109.01500000,
                'area' => 'Cilacap',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Toko Berkah Utama',
                'address' => 'Jl. Ahmad Yani No. 45, Cilacap',
                'latitude' => -7.72000000,
                'longitude' => 109.02000000,
                'area' => 'Cilacap',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Frozen Food Sejahtera',
                'address' => 'Jl. Gatot Subroto No. 88, Cilacap',
                'latitude' => -7.71200000,
                'longitude' => 109.01000000,
                'area' => 'Cilacap',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}