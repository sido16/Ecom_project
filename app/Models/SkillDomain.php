<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillDomain extends Model
{
    protected $table = 'skill_domain';
    protected $fillable = ['name'];

    public function skills()
    {
        return $this->hasMany(Skill::class, 'domain_id');
    }
}
?>