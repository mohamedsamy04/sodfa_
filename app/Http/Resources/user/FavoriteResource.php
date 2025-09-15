<?php

namespace App\Http\Resources\user;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class FavoriteResource extends JsonResource
{
    public function toArray($request)
    {
        // تحويل created_at و updated_at لـ string
        $createdAt = $this->created_at instanceof \Carbon\Carbon
            ? $this->created_at->toDateTimeString()
            : (string) $this->created_at;

        $updatedAt = $this->updated_at instanceof \Carbon\Carbon
            ? $this->updated_at->toDateTimeString()
            : (string) $this->updated_at;

        return [
            'id' => $this->id,
            'product' => $this->whenLoaded('product', function () {
                return [
                    'id'    => $this->product->id,
                    'name'  => $this->product->name,
                    'price' => $this->product->price,
                    'description' => $this->product->description,
                    'is_featured' => $this->product->is_featured,
                    'product_advantage' => $this->product->product_advantage ?? null,
                    'category_name' => $this->product->category->name,
                    'colors' => $this->product->productColorImages
                        ->groupBy('color_id')
                        ->map(function ($images) {
                            $color = $images->first()->color;

                            return [
                                'id'   => $color->id,
                                'name' => $color->name ?? null,
                                'hex'  => $color->hex_code,
                                'images'     => $images->map(function ($img) {
                                    return $img->image ? asset('storage/' . $img->image) : null;
                                })->filter()->values(),
                            ];
                        })->values(),
                ];
            }),
            'created_at' => $createdAt,
            'updated_at' => $updatedAt,
        ];
    }
}
