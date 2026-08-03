<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class SupportRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_request_id',
        'reference',
        'user_id',
        'name',
        'email',
        'topic',
        'message',
        'item_type',
        'item_id',
        'item_title',
        'city_slug',
        'item_slug',
        'page_url',
        'status',
        'traveler_notified_at',
        'traveler_notification_failed_at',
        'support_notified_at',
        'support_notification_failed_at',
    ];

    protected $casts = [
        'traveler_notified_at' => 'datetime',
        'traveler_notification_failed_at' => 'datetime',
        'support_notified_at' => 'datetime',
        'support_notification_failed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function item(): MorphTo
    {
        return $this->morphTo();
    }
}
