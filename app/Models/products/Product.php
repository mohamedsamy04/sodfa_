<?php

namespace App\Models\products;

use App\Models\user\Favorite;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\user\OrderItem;
class Product extends BaseModel
{
    use HasFactory;

    protected $fillable = [
        'name',
        'category_id',
        'description',
        'product_advantage',
        'price',
        'is_featured'
    ];
    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function productColorImages()
    {
        return $this->hasMany(ProductColorImage::class);
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

}
