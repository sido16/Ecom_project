<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    protected $primaryKey = 'id';

    protected $fillable = ['user_id', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function skills()
    {
        return $this->belongsToMany(CreativeSkill::class, 'service_provider_skills', 'service_provider_id', 'skill_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'provider_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'provider_id');
    }
}