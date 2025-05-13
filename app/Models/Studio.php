<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Studio extends Model
{
    use HasFactory;

    protected $table = 'studio';

    protected $fillable = [
        'workspace_id',
        'price_per_hour',
        'price_per_day',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }

    public function services()
    {
        return $this->belongsToMany(StudioService::class, 'offered_services', 'studio_id', 'studio_service_id');
    }
}