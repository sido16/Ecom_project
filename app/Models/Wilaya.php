<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wilaya extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
    ];

    public function communes()
    {
        return $this->hasMany(Commune::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
