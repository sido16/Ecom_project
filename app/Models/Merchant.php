<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    protected $table = 'merchants';
    protected $fillable = ['supplier_id'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}