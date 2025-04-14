<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreativeSkill extends Model
{
    protected $fillable = ['name'];

    public function serviceProviders()
    {
        return $this->belongsToMany(ServiceProvider::class, 'service_provider_skills', 'skill_id', 'service_provider_id');
    }
}