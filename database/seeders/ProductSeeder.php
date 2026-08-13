<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('products')->insert([
            [
                'name' => 'Fiva Food Sosis Ayam 500g',
                'weight' => 500.00,
                'stock' => 100,
                'price' => 35000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fiva Food Bakso Sapi 250g',
                'weight' => 250.00,
                'stock' => 150,
                'price' => 28000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Fiva Food Chicken Nugget 400g',
                'weight' => 400.00,
                'stock' => 80,
                'price' => 42000.00,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}