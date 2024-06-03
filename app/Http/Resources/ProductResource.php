<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray($request)
    {
        $discount = $this->discounts->first();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ],
            'brand' => [
                'id' => $this->brand->id,
                'name' => $this->brand->name,
                'logo_url' => $this->brand->logo_url,
            ],
            'price' => (float)$this->price,
            'images' => $this->images,
            'description' => $this->description,
            'is_new_arrival' => $this->is_new_arrival,
            'discount' => $discount ? [
                'id' => $discount->id,
                'name' => $discount->name,
                'percentage' => (float) $discount->percentage,
                'start_date' => $discount->start_date,
                'end_date' => $discount->end_date,
            ] : null,
        ];
    }
}
