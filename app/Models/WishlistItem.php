<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WishlistItem extends Model
{
    use HasFactory;

    public const TYPE_ACTIVITY = 'activity';

    public const TYPE_ITINERARY = 'itinerary';

    public const TYPE_PACKAGE = 'package';

    public const TYPE_TRANSFER = 'transfer';

    public const SUPPORTED_TYPES = [
        self::TYPE_ACTIVITY,
        self::TYPE_ITINERARY,
        self::TYPE_PACKAGE,
        self::TYPE_TRANSFER,
    ];

    protected $fillable = [
        'user_id',
        'item_type',
        'item_id',
        'title',
        'slug',
        'city_slug',
        'city_name',
        'image_url',
        'price',
        'currency',
        'snapshot',
    ];

    protected $casts = [
        'item_id' => 'integer',
        'price' => 'decimal:2',
        'snapshot' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }
}
