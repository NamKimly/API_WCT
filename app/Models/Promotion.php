<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'description'];

    public function products()
    {
        return $this->belongsToMany(Products::class, 'promotion_product', 'promotion_id', 'product_id')
            ->withPivot('is_free');
    }
}
