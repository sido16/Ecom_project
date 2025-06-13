<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'full_name',
        'phone_number',
        'email',
        'password',
        'role',
        'picture',
        'address',
        'city'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function suppliers()
    {
        return $this->hasMany(Supplier::class, 'user_id');
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'user_id');
    }

    public function events()
    {
        return $this->hasMany(Event::class, 'organizer_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function serviceProvider()
    {
        return $this->hasOne(ServiceProvider::class, 'user_id');
    }

    public function savedServiceProviders()
    {
        return $this->belongsToMany(ServiceProvider::class, 'saved_service_providers', 'user_id', 'service_provider_id')
                    ->withTimestamps();
    }

    public function savedWorkspaces()
    {
        return $this->belongsToMany(Workspace::class, 'saved_workspaces', 'user_id', 'workspace_id')
                    ->withTimestamps();
    }

    public function savedProducts()
    {
        return $this->belongsToMany(Product::class, 'saved_products', 'user_id', 'product_id')
                    ->withTimestamps();
    }

    public function savedSuppliers()
    {
        return $this->belongsToMany(Supplier::class, 'saved_suppliers', 'user_id', 'supplier_id')
                    ->withTimestamps();
    }
}
?>
