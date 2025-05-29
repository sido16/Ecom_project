<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductPicture extends Model
{
    protected $fillable = ['product_id', 'picture'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productFeature()
    {
        return $this->hasOne(ProductFeature::class, 'image_id');
    }
}