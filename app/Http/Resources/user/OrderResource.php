<?php

namespace App\Http\Resources\User;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request)
    {
        return [
            'id'            => $this->id,
            'user_id'       => $this->user_id,
            'name'          => $this->name,
            'phone'         => $this->phone,
            'email'         => $this->email,
            'city'          => $this->city,
            'address'       => $this->address,
            'status'        => $this->status,
            'total_price'   => (float) $this->total_price,
            'created_at'    => $this->created_at,
            'updated_at'    => $this->updated_at,

            // ====== الإلغاء ======
            'cancellation_reason' => $this->when(
                $this->status === 'canceled' && !empty($this->cancellation_reason),
                $this->cancellation_reason
            ),

            // ====== الاسترجاع ======
            'return_reason' => $this->when(
                !empty($this->return_reason),
                $this->return_reason
            ),
            'return_status' => $this->when(
                !empty($this->return_status),
                $this->return_status
            ),

            // ====== المنتجات ======
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),

            // ====== الدفع ======
            'payment_method' => $this->when(
                $this->relationLoaded('paymentMethod'),
                [
                    'id'            => $this->paymentMethod?->id,
                    'method'        => match ($this->paymentMethod?->method) {
                        'vodafone_cash' => 'Vodafone Cash',
                        'instapay'      => 'Instapay'
                    },
                    'receipt_image' => $this->paymentMethod?->receipt_image,
                    'created_at'    => $this->paymentMethod?->created_at,
                    'updated_at'    => $this->paymentMethod?->updated_at,
                ]
            ),
        ];
    }

    /**
     * Additional data to return with the resource.
     */
    public function with($request)
    {
        return [
            'success' => true,
        ];
    }
}
