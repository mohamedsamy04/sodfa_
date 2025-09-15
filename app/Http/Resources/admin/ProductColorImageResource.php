<?php

namespace App\Http\Resources\admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductColorImageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray($request)
    {
        return [
            'id'        => $this->id,
            'image_url' => $this->image ? asset('storage/' . $this->image) : null,
            'color'     => new ColorResource($this->whenLoaded('color')),
        ];
    }
}
