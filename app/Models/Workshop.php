<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    protected $table = 'work_shops';
    protected $fillable = ['supplier_id'];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}