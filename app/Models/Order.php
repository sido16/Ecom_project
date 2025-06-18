<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'supplier_id',
        'wilaya_id',
        'commune_id',
        'full_name',
        'phone_number',
        'status',
        'total_amount',
        'order_date',
        'is_validated',
        'address',
    ];

    protected $casts = [
        'status' => 'string',
        'order_date' => 'datetime',
        'is_validated' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function wilaya()
    {
        return $this->belongsTo(Wilaya::class);
    }

    public function commune()
    {
        return $this->belongsTo(Commune::class);
    }

    public function orderProducts()
    {
        return $this->hasMany(OrderProduct::class);
    }

    public function routeNotificationForDatabase()
    {
        return $this->supplier_id; // Notify the supplier
    }
}
