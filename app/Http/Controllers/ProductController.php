<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function create(Request $request){
        Product::create($request->all());
    }

    public function show($id)
    {
        $product = Product::find($id);

        if($product!=null) {
            return response()->json($product);
        }

        return response()->json(['message' => 'Product not found'], 404);
    }

    public function index(Request $request)
    {
        $query = Product::query();

        // Сортировка по цене
        if ($request->has('sort_by_price')) {
            $query->orderBy('price', $request->get('sort_by_price'));
        }

        return response()->json($query->get());
    }
}
