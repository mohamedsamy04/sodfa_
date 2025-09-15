<?php

namespace App\Http\Controllers\user;

use App\Http\Controllers\Controller;
use App\Http\Resources\user\ProductResource;
use App\Models\products\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{

    public function allProducts(Request $request)
    {
        $query = Product::with(['productColorImages.color']);
        $products = $query->paginate(12);
        return ProductResource::collection($products);
    }

    public function home()
    {
        $products = Product::with(['productColorImages.color'])
                           ->where('is_featured', true)
                           ->paginate(12);

        return ProductResource::collection($products);
    }
}
