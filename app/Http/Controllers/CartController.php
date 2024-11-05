<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function addProduct(Request $request)
    {
        $cart = auth()->user()->cart()->firstOrCreate([]);

        // Получаем товар по ID
        $product = Product::find($request->product_id);

        if($product == null) {
            return response()->json(['message' => 'Product not found'],404);
        }

        // Добавляем товар в корзину
        $cart->products()->attach($product);

        return response()->json(['message' => 'Товар добавлен в корзину']);
    }

    public function removeProduct($productId)
    {
        $cart = auth()->user()->cart;
        $cart->products()->detach($productId);

        return response()->json(['message' => 'Товар удален из корзины']);
    }
}
