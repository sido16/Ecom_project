<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceOrder extends Model
{
    use HasFactory;

    protected $table = 'service_orders';

    protected $fillable = [
        'user_id',
        'service_provider_id',
        'skill_id',
        'title',
        'description',
        'deadline',
        'total_amount',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
        'total_amount' => 'decimal:2',
        'deadline' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class);
    }

    public function rating()
    {
        return $this->hasOne(ServiceProviderRating::class);
    }
}