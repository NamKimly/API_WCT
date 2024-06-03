<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Brand extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'logo_url'];
    protected $hidden = ['created_at', 'updated_at'];

    public function products()
    {
        return $this->hasMany(Products::class);
    }
}
