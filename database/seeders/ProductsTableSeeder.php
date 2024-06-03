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
            'name' => 'Sample Product 1',
            'category_id' => 1, // Ensure this ID exists in your categories table
            'brand_id' => 1,    // Ensure this ID exists in your brands table
            'price' => 100,
            'images' => 'sample1.jpg',
            'description' => 'Sample description for product 1'
        ]);
    }
}
