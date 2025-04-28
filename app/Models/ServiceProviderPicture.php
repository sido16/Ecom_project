<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProviderPicture extends Model
{
    protected $table = 'service_provider_pictures';
    protected $fillable = ['service_provider_id', 'picture'];

    public function serviceProvider()
    {
        return $this->belongsTo(ServiceProvider::class, 'service_provider_id');
    }
}
?>