<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'percentage' => (float) $this->percentage,
            'start_date' => $this->start_date,
            'end_date' => $this->end_date,
            'product' => $this->product ? [
                'id' => $this->product->id,
                'name' => $this->product->name,
                'category' => $this->product->category->name ?? 'N/A',
                'brand' => $this->product->brand->name ?? 'N/A',
                'price' => $this->product->price,
                'images' => $this->product->images,
                'description' => $this->product->description,
                'is_new_arrival' => $this->product->is_new_arrival,
            ] : null,
        ];
    }
}
