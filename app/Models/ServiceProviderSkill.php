<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ServiceProviderSkill extends Pivot
{
    protected $table = 'service_provider_skill';
    protected $fillable = ['service_provider_id', 'skill_id'];

    public $incrementing = false;

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
    }

    public function skill()
    {
        return $this->belongsTo(Skill::class, 'skill_id');
    }
}
?>