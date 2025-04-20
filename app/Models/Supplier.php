<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = [
        'user_id',
        'business_name',
        'address',
        'description',
        'picture',
        'domain_id',
    ];
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }

    public function workshop()
    {
        return $this->hasOne(Workshop::class, 'supplier_id');
    }

    public function importer()
    {
        return $this->hasOne(Importer::class, 'supplier_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'supplier_id');
    }
}