<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray($request)
    {
        $product = $this->product;
        $color = $product
            ? $product->productColorImages()
            ->where('color_id', $this->color_id)
            ->first()?->color
            : null;

        $images = $product
            ? $product->productColorImages()
            ->where('color_id', $this->color_id)
            ->pluck('image')
            ->map(fn($img) => asset('storage/' . $img))
            : [];
        return [
            'id'         => $this->id,
            'product_id' => $this->product_id,
            'quantity'   => $this->quantity,
            'price'      => (float) $this->price,
            'subtotal'   => (float) $this->subtotal,
            'product'    => $this->when($product, [
                'id'    => $product->id,
                'name'  => $product->name,
                'price' => (float) $product->price,
                'color' => $color ? [
                    'id'   => $color->id,
                    'name' => $color->name,
                    'hex'  => $color->hex_code,
                ] : null,
                'images' => $images,
            ]),
        ];
    }
}
