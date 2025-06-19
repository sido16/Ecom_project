<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Supplier extends Model
{
    use Notifiable;

    protected $fillable = [
        'user_id',
        'business_name',
        'address',
        'description',
        'picture',
        'domain_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class, 'domain_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'supplier_id');
    }

    public function workshop()
    {
        return $this->hasOne(Workshop::class, 'supplier_id');
    }

    public function importer()
    {
        return $this->hasOne(Importer::class, 'supplier_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'supplier_id');
    }

    public function savedByUsers()
    {
        return $this->belongsToMany(User::class, 'saved_suppliers', 'supplier_id', 'user_id')
                    ->withTimestamps();
    }

    public function isSavedByUser($userId)
    {
        return $this->savedByUsers()->where('user_id', $userId)->exists();
    }
}
