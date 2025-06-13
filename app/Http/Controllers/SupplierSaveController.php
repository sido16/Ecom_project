<?php

namespace App\Http\Controllers;

use App\Models\Supplier;

class SupplierSaveController extends SaveController
{
    public function __construct()
    {
        $this->model = Supplier::class;
        $this->relationName = 'savedSuppliers';
        $this->idField = 'supplier_id';
    }
}
