<?php

namespace App\Http\Resources\admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'description'  => $this->description,
            'product_advantage'=> $this->product_advantage ?? null,
            'category_name'=> $this->category->name,
            'category_id' => $this->category_id,
            'is_featured' => $this->is_featured,
            'created_at' => $this->created_at,
            'colors' => $this->productColorImages
                ->groupBy('color_id')
                ->map(function ($images) {
                    $color = $images->first()->color;

                    return [
                        'id'   => $color->id,
                        'name' => $color->name ?? null,
                        'hex'  => $color->hex_code ?? null,
                        'images' => $images->map(function ($img) {
                            return $img->image ? asset('storage/' . $img->image) : null;
                        })->filter()->values(),
                    ];
                })->values(),
        ];
    }
}
