<?php

namespace App\Models\user;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\products\BaseModel;
use App\Models\products\Product;

class Cart extends BaseModel
{
    use HasFactory;
    protected $fillable = ['user_id', 'status', 'price', 'expires_at'];

    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function add($productId, $quantity)
    {
        $item = $this->cartItems()->where('product_id', $productId)->first();
        if ($item)
        {
            $item->quantity += $quantity;
            $item->price = $item->quantity * $item->product->price;
            $item->save();
        }
        else
        {
            $product = Product::findOrFail($productId);
            $this->cartItems()->create([
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $quantity * $product->price,
                

            ]);
        }

        $this->price = $this->cartItems()->sum('price');
        $this->save();
    }
}
