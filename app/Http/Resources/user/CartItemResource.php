<?php

namespace App\Http\Resources\user;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'quantity' => $this->quantity,
            'total' => $this->quantity * $this->product->price,
            'color_id' => $this->color_id,
            'color' => $this->color ? [
                'id'   => $this->color->id,
                'name' => $this->color->name,
                'hex'  => $this->color->hex_code ?? null,
            ] : null,
            'product' => [
                'id'    => $this->product->id,
                'name'  => $this->product->name,
                'price' => $this->product->price,
                'images' => $this->product->productColorImages
                    ->where('color_id', $this->color_id)
                    ->pluck('image')
                    ->toArray(),
            ],
        ];
    }
}
