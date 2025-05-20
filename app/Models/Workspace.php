<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Workspace extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'business_name',
        'type',
        'phone_number',
        'email',
        'location',
        'address',
        'description',
        'opening_hours',
        'picture',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'type' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function coworking()
    {
        return $this->hasOne(Coworking::class, 'workspace_id');
    }

    public function studio()
    {
        return $this->hasOne(Studio::class, 'workspace_id');
    }

    public function images()
    {
        return $this->hasMany(WorkspaceImage::class, 'workspace_id');
    }

    public function workingHours()
    {
        return $this->hasMany(WorkingHour::class, 'workspace_id');
    }
}