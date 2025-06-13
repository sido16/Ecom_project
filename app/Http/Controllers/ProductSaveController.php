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
}
