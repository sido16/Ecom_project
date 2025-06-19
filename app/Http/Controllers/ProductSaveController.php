<?php

namespace App\Http\Controllers;

use App\Models\Product;

class ProductSaveController extends SaveController
{
    public function __construct()
    {
        $this->model = Product::class;
        $this->relationName = 'savedProducts';
        $this->idField = 'product_id';
    }

    public function getSaved(): \Illuminate\Http\JsonResponse
    {
        $user = \Auth::user();
        $savedProducts = $user->savedProducts()->with(['pictures', 'supplier'])->get();

        return response()->json([
            'message' => 'Saved products retrieved successfully',
            'data' => $savedProducts,
        ], 200);
    }
}
