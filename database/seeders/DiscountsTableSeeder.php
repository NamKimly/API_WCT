<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Discount;
use Carbon\Carbon;

class DiscountsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Discount::create([
            'name' => 'Sample Discount 1',
            'percentage' => 10,
            'start_date' => Carbon::now(),
            'end_date' => Carbon::now()->addMonth()
        ]);
    }
}
