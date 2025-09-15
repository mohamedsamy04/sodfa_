<?php

namespace App\Models\user;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\products\Product;
use App\Models\products\BaseModel;
use App\Models\products\Color;

class CartItem extends BaseModel
{
    use HasFactory;
    protected $fillable = ['cart_id' , 'product_id' , 'quantity' , 'price' , 'color_id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }
    public function color()
    {
        return $this->belongsTo(Color::class);
    }
}
