<?php

namespace App\Http\Resources\admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'stats' => [
                'products' => [
                    'total' => $this['total_products'],
                ],
                'users' => [
                    'total'   => $this['total_users'],
                    'daily'   => $this['users_today'],
                    'monthly' => $this['users_this_month'],
                    'yearly'  => $this['users_this_year'],
                ],
                'orders' => [
                    'total'   => $this['total_orders'],
                    'daily'   => $this['orders_today'],
                    'monthly' => $this['orders_this_month'],
                    'yearly'  => $this['orders_this_year'],
                ],
                'revenue' => [
                    'total'   => $this['total_revenue'],
                    'daily'   => $this['revenue_today'],
                    'monthly' => $this['revenue_this_month'],
                    'yearly'  => $this['revenue_this_year'],
                ],
                'processing_orders' => [
                    'today' => $this['processing_orders_today'],
                    'month' => $this['processing_orders_month'],
                    'year'  => $this['processing_orders_year'],
                ],
            ],
            'top_3_best_selling' => $this['top_3_best_selling']->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'total_sold' => $product->total_sold,
                ];
            }),
        ];
    }
}
