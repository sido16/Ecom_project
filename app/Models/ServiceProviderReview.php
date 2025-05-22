<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ServiceProviderReview extends Model
{
    protected $table = 'service_provider_reviews';

    protected $fillable = [
        'service_provider_id',
        'user_id',
        'rating',
        'comment',
        'reply',
        'reply_user_id',
        'reply_created_at',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'reply_created_at' => 'datetime',
    ];

    public function serviceProvider(): BelongsTo
    {
        return $this->belongsTo(ServiceProvider::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function replyUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reply_user_id');
    }
}
