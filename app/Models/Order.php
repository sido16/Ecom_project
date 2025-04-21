<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'status',
        'total_amount',
        'order_date',
        'is_validated',
    ];

    protected $casts = [
        'status' => 'string',
        'total_amount' => 'decimal:2',
        'order_date' => 'datetime',
        'is_validated' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class);
    }
}