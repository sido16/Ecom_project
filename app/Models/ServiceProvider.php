<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    protected $table = 'service_providers';
    protected $fillable = ['user_id', 'skill_domain_id', 'description', 'starting_price'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function skillDomain()
    {
        return $this->belongsTo(SkillDomain::class, 'skill_domain_id');
    }

    public function skills()
    {
        return $this->belongsToMany(Skill::class, 'service_provider_skill', 'service_provider_id', 'skill_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'service_provider_id');
    }

    public function pictures()
    {
        return $this->hasMany(ServiceProviderPicture::class, 'service_provider_id');
    }
}
?>