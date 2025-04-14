<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['supplier_id', 'product_name', 'description', 'category_id', 'price', 'quantity', 'minimum_quantity'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function pictures()
    {
        return $this->hasMany(ProductPicture::class, 'product_id');
    }
}