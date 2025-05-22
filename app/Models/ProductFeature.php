<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductFeature extends Model
{
    use HasFactory;

    protected $fillable = ['image_id', 'features'];

    protected $casts = [
        'features' => 'array'
    ];

    public function productPicture()
    {
        return $this->belongsTo(ProductPicture::class, 'image_id');
    }
}