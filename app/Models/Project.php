<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    protected $table = 'projects';
    protected $fillable = ['service_provider_id', 'title', 'description'];

   

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
    }

    public function pictures()
    {
        return $this->hasMany(ProjectPicture::class, 'project_id');
    }
}
?>