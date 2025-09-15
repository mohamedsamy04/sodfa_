<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\user\Cart;
use App\Models\user\CartItem;
use App\Models\products\Product;
use App\Http\Resources\user\ProductResource;
use App\Http\Resources\user\CartResource;

class CartController extends Controller

{public function index()
    {
        $cart = Cart::where('user_id', auth()->id())
            ->where('status', 'processing')
            ->with(['cartItems.product', 'cartItems.color'])
            ->first();

        // لو مفيش cart خالص
        if (!$cart) {
            return response()->json([
                'message' => 'Cart is empty'
            ]);
        }

        // لو الـ cart موجود بس فاضي (مفيش items)
        if ($cart->cartItems->isEmpty()) {
            return response()->json([
                'message' => 'Cart is empty'
            ]);
        }

        // لو الـ cart موجود وفيه items - نجيب الـ featured products
        $featuredProducts = Product::where('is_featured', true)
            ->with(['productColorImages.color', 'category'])
            ->paginate(12); // 12 منتجات في الصفحة

        return response()->json([
            'cart' => new CartResource($cart),
            'featured_products' => ProductResource::collection($featuredProducts),
            'pagination' => [
                'current_page' => $featuredProducts->currentPage(),
                'per_page' => $featuredProducts->perPage(),
                'total' => $featuredProducts->total(),
                'last_page' => $featuredProducts->lastPage(),
            ],
            'message' => 'Cart found'
        ]);
    }

    public function store(Request $request)
{
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'quantity' => 'required|integer|min:1',
        'color_id' => 'required|exists:colors,id',
    ]);

    $product = Product::findOrFail($request->product_id);

    // ✅ التحقق إن اللون مرتبط بالمنتج
    $colorExistsForProduct = $product->productColorImages()
        ->where('color_id', $request->color_id)
        ->exists();

    if (!$colorExistsForProduct) {
        return response()->json([
            'message' => 'This color is not available for the selected product'
        ], 422);
    }

    $cart = User::find(auth()->id())->carts()->firstOrCreate(
        ['status' => 'processing'],
        [
            'price' => 0,
            'expires_at' => now()->addDays(3),
        ]
    );

    // البحث بالمنتج واللون معاً
    $cartItem = $cart->cartItems()
        ->where('product_id', $request->product_id)
        ->where('color_id', $request->color_id)
        ->first();

    if ($cartItem) {
        $cartItem->quantity += $request->quantity;
        $cartItem->price = $cartItem->quantity * $product->price;
        $cartItem->save();
    } else {
        $cart->cartItems()->create([
            'product_id' => $request->product_id,
            'quantity' => $request->quantity,
            'price' => $request->quantity * $product->price,
            'color_id' => $request->color_id
        ]);
    }

    $cart->price = $cart->cartItems()->sum('price');

    if (!$cart->expires_at || $cart->expires_at < now()) {
        $cart->expires_at = now()->addDays(3);
    }

    $cart->save();

    return response()->json([
        'message' => 'Product added to cart',
        'cart' => new CartResource($cart->fresh(['cartItems.product', 'cartItems.color']))
    ]);
}


    public function update(Request $request, $id)
    {
        $cartItem = CartItem::find($id);

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }

        $request->validate([
            'quantity' => 'required|integer|min:1',
            'color_id' => 'required|exists:colors,id',
        ]);

        if ($cartItem->cart->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // ✅ التحقق إن اللون فعلاً مرتبط بالمنتج ده
        $colorExistsForProduct = $cartItem->product
            ->productColorImages()
            ->where('color_id', $request->color_id)
            ->exists();

        if (!$colorExistsForProduct) {
            return response()->json([
                'message' => 'This color is not available for the selected product'
            ], 422);
        }

        // لو اللون اتغير
        if ($cartItem->color_id != $request->color_id) {
            $existingItem = $cartItem->cart->cartItems()
                ->where('product_id', $cartItem->product_id)
                ->where('color_id', $request->color_id)
                ->where('id', '!=', $cartItem->id)
                ->first();

            if ($existingItem) {
                $existingItem->quantity += $request->quantity;
                $existingItem->price = $existingItem->quantity * $cartItem->product->price;
                $existingItem->save();

                $cartItem->delete();
                $cartItem = $existingItem;
            } else {
                $cartItem->color_id = $request->color_id;
                $cartItem->quantity = $request->quantity;
                $cartItem->price = $request->quantity * $cartItem->product->price;
                $cartItem->save();
            }
        } else {
            // تحديث الكمية فقط
            $cartItem->quantity = $request->quantity;
            $cartItem->price = $cartItem->quantity * $cartItem->product->price;
            $cartItem->save();
        }

        $cart = $cartItem->cart;
        $cart->price = $cart->cartItems()->sum('price');

        if (!$cart->expires_at || $cart->expires_at < now()) {
            $cart->expires_at = now()->addDays(3);
        }

        $cart->save();

        return response()->json([
            'message' => 'Cart item updated',
            'cart' => new CartResource($cart->fresh(['cartItems.product', 'cartItems.color']))
        ]);
    }


    public function destroy($id)
    {
        $cartItem = CartItem::find($id);

        if (!$cartItem) {
            return response()->json(['message' => 'Cart item not found'], 404);
        }
        if ($cartItem->cart->user_id !== auth()->id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cart = $cartItem->cart;
        $cartItem->delete();

        $cart->price = $cart->cartItems()->sum('price');

        if ($cart->cartItems()->count() > 0) {
            $cart->expires_at = $cart->expires_at ?? now()->addDays(3);
        } else {
            $cart->expires_at = null;
        }

        $cart->save();

        return response()->json([
            'message' => 'Cart item deleted',
            'cart' => new CartResource($cart->fresh(['cartItems.product', 'cartItems.color']))
        ]);
    }
    public function clear()
    {
        $cart = Cart::where('user_id', auth()->id())
                    ->where('status', 'processing')
                    ->first();

        if (!$cart) {
            return response()->json([
                'cart' => null,
                'message' => 'Cart not found'
            ], 404);
        }

        if ($cart->cartItems()->count() === 0) {
            return response()->json([
                'message' => 'Cart is already empty'
            ]);
        }

        $cart->cartItems()->delete();
        $cart->price = 0;
        $cart->expires_at = null;
        $cart->save();

        return response()->json([
            'message' => 'Cart cleared',
            'cart' => new CartResource($cart->fresh(['cartItems.product', 'cartItems.color']))
        ]);
    }

}
