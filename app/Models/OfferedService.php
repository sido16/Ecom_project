<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class OfferedService extends Pivot
{
    use HasFactory;

    protected $table = 'offered_services';

    protected $fillable = [
        'studio_id',
        'studio_service_id',
    ];

    public function studio()
    {
        return $this->belongsTo(Studio::class, 'studio_id');
    }

    public function studioService()
    {
        return $this->belongsTo(StudioService::class, 'studio_service_id');
    }
}