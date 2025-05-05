<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SkillDomain extends Model
{
    protected $table = 'skill_domains';
    protected $fillable = ['name'];


    public function serviceProviders()
    {
        return $this->hasMany(ServiceProvider::class, 'skill_domain_id');
    }
}
?>
