<?php

namespace Database\Seeders;

use App\Models\Products;

use Illuminate\Database\Seeder;

class ProductsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Products::create([
            'name' => 'BRAVIA',
            'category_id' => 2,
            'brand_id' => 1,
            'price' => 300.99,
            'images' => 'https://d1ncau8tqf99kp.cloudfront.net/converted/111973_original_local_1200x1050_v3_converted.webp',
            'description' => 'Indulge in the rich aroma and robust flavor of our carefully curated coffee blend. Sourced from the finest beans around the world, our coffee offers a delightful balance of boldness and smoothness with every sip.'
        ]);
    }
}
