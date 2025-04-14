<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    protected $table = 'work_shops';
    protected $primaryKey = 'supplier_id';
    protected $fillable = ['supplier_id'];
    public $incrementing = false;

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id', 'id');
    }
}