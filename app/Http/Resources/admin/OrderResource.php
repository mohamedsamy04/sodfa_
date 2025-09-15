<?php

namespace App\Http\Resources\admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Models\user\Order;
use App\Http\Resources\user\OrderItemResource;

class OrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'phone' => $this->phone,
            'city' => $this->city,
            'email' => $this->email,
            'address' => $this->address,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'cancellation_reason' => $this->when(
                $this->status === 'canceled',
                $this->cancellation_reason
            ),
            'return_request' => $this->when(
                $this->return_reason && $this->return_status,
                [
                    'status'       => $this->return_status,
                    'reason'       => $this->return_reason,
                    'requested_at' => $this->return_requested_at,
                    'handled_at'   => $this->return_handled_at
                ]
            ),

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'payment_method' => $this->whenLoaded('paymentMethod', function () {
                return [
                    'method' => $this->paymentMethod->method,
                    'receipt_image' => $this->paymentMethod->receipt_image,
                ];
            }),
            'items' => OrderItemResource::collection($this->whenLoaded('orderItems')),
        ];
    }
}
