<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coworking extends Model
{
    use HasFactory;

    protected $table = 'coworking';

    protected $fillable = [
        'workspace_id',
        'price_per_day',
        'price_per_month',
        'seating_capacity',
        'meeting_rooms',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }
}