<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Category;

class CategoriesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Small Kitchen Appliance',
            'Cookware',
            'Large Appliance',
            'Cooking and Heating',
            'Floor care and Garment',
            'LED TV and Audio',
            'Personal Care',
        ];

        foreach ($categories as $category) {
            Category::create([
                'name' => $category,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
