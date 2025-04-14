<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Importer extends Model
{
    protected $fillable = ['supplier_id'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}