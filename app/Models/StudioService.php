<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudioService extends Model
{
    use HasFactory;

    protected $fillable = [
        'service',
    ];

    public function studios()
    {
        return $this->belongsToMany(Studio::class, 'offered_services', 'studio_service_id', 'studio_id');
    }
}