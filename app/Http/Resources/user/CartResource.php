<?php

namespace App\Http\Resources\user;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'status' => $this->status,
            'subtotal' => $this->cartItems->sum(fn($item) => $item->quantity * $item->product->price),
            'total' => $this->cartItems->sum(fn($item) => $item->quantity * $item->product->price),
            'items' => CartItemResource::collection($this->cartItems),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'expires_at' => $this->expires_at ? Carbon::parse($this->expires_at)->format('d/m/Y H:i') : null,

        ];
    }
}
