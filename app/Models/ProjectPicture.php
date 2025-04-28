<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectPicture extends Model
{
    protected $table = 'project_pictures';
    protected $fillable = ['project_id', 'picture'];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }
}
?>