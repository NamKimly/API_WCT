<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'category_id', 'brand_id', 'price', 'images', 'description', 'is_new_arrival'];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    // Define the relationship with the Brand model
    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function discounts()
    {
        return $this->hasMany(Discount::class, 'product_id', 'id');
    }
}
