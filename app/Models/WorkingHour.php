<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WorkingHour extends Model
{
    use HasFactory;

    protected $table = 'working_hours';

    protected $fillable = [
        'workspace_id',
        'day',
        'time_from',
        'time_to',
        'is_open',
    ];

    protected $casts = [
        'is_open' => 'boolean',
    ];

    public function workspace()
    {
        return $this->belongsTo(Workspace::class, 'workspace_id');
    }
}