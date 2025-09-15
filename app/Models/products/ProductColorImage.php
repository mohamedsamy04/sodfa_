<?php

namespace App\Models\products;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProductColorImage extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'color_id',
        'image'
    ];
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }
}
