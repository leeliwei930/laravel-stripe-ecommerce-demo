<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function products()
    {
        $products= Product::all();

        return response()->json($products->toArray(), 200);
    }
}
