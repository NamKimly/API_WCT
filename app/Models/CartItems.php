<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItems extends Model
{
    use HasFactory;
    protected $fillable = ['cart_id', 'product_id', 'total_price', 'quantity'];
    protected $hidden = ['created_at', 'updated_at'];

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    public function product()
    {
        return $this->belongsTo(Products::class);
    }
}
