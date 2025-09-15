<?php

namespace App\Http\Resources\user;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'price'        => $this->price,
            'description'  => $this->description,
            'is_featured'  => $this->is_featured,
            'product_advantage'=> $this->product_advantage ?? null,
            'category_name'=> $this->category->name,
            'colors' => $this->productColorImages
                ->groupBy('color_id')
                ->map(function ($images) {
                    $color = $images->first()->color;

                    return [
                        'id'   => $color->id,
                        'name' => $color->name ?? null,
                        'hex'  => $color->hex_code ?? null,
                        'images'     => $images->map(function ($img) {
                            return $img->image ? asset('storage/' . $img->image) : null;
                        })->filter()->values(),
                    ];
                })->values(),
            'created_at'   => $this->created_at,
            'sales_count'  => $this->when(isset($this->order_items_sum_quantity), $this->order_items_sum_quantity),
        ];
    }

}
