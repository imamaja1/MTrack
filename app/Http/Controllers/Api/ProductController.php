<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $adminId = $request->user()->role === 'admin' ? $request->user()->id : $request->user()->admin_id;

        $products = Product::where('admin_id', $adminId)->get();

        return response()->json([
            'data' => $products,
        ]);
    }
}
