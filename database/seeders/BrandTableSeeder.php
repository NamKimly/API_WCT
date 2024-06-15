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

        foreach ($brands as $brand) {
            Brand::create($brand);
        }
    }
}
