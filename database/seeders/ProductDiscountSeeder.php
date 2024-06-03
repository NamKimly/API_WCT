<?php

namespace Database\Seeders;

use App\Models\Discount;
use App\Models\Products;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductDiscountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $product1 = Products::find(1); // Assuming the first product has ID 1

        $discount1 = Discount::find(1); // Assuming the first discount has ID 1

        if ($product1 && $discount1) {
            $product1->discounts()->attach($discount1);
        }
    }
}
