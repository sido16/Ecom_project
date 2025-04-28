<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProvider extends Model
{
    protected $table = 'service_providers';
    protected $fillable = ['user_id', 'description'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
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