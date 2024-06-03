<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            // Ensure that the category_id column references the id column in the categories table
            $table->foreignId('category_id')
                ->constrained('categories')
                ->onDelete('cascade');

            // Ensure that the brand_id column references the id column in the brands table
            $table->foreignId('brand_id')
                ->constrained('brands')
                ->onDelete('cascade');

            $table->decimal('price', 8, 2);

            $table->string('images');
            $table->text('description');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the products table if it exists
        Schema::dropIfExists('products');
    }
}
