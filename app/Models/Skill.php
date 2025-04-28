<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    protected $table = 'skills';
    protected $fillable = ['name', 'domain_id'];

    public function domain()
    {
        return $this->belongsTo(SkillDomain::class, 'domain_id');
    }

    public function serviceProviders()
    {
        return $this->belongsToMany(ServiceProvider::class, 'service_provider_skill', 'skill_id', 'service_provider_id');
    }
}
?>