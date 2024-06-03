<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BrandTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $brands = [
            [
                'name' => 'Sony',
                'logo_url' => 'https://brandslogos.com/wp-content/uploads/images/large/sony-logo-1.png',
            ],

        ];

        // Insert brand data into the brands table
        foreach ($brands as $brand) {
            Brand::create([
                'name' => $brand['name'],
                'logo_url' => $brand['logo_url'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
