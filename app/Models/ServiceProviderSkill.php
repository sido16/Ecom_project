<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ServiceProviderSkill extends Pivot
{
    protected $table = 'service_provider_skills';

    protected $fillable = ['service_provider_id', 'skill_id'];
}