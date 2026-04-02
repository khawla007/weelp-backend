<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $order_id
 * @property string $item_type
 * @property int $item_id
 * @property string|null $item_name_snapshot
 * @property string|null $item_slug_snapshot
 * @property int $rating
 * @property string|null $review_text
 * @property string $status
 * @property bool $is_featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent $item
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ReviewMediaGallery> $mediaGallery
 * @property-read int|null $media_gallery_count
 * @property-read \App\Models\Order|null $order
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereItemId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereItemNameSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereItemSlugSnapshot($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereItemType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereReviewText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereUserId($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Review extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'item_type',
        'item_id',
        'item_name_snapshot',
        'item_slug_snapshot',
        'rating',
        'review_text',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    // Polymorphic relation
    public function item(): MorphTo
    {
        return $this->morphTo(__FUNCTION__, 'item_type', 'item_id');
    }

    public function mediaGallery(): HasMany
    {
        return $this->hasMany(ReviewMediaGallery::class)->orderBy('sort_order');
    }

    /**
     * Get display name - uses snapshot if item deleted
     */
    public function getDisplayName(): string
    {
        return $this->item->name ?? $this->item_name_snapshot ?? 'Archived Item';
    }

    /**
     * Get display slug - uses snapshot if item deleted
     */
    public function getDisplaySlug(): ?string
    {
        return $this->item->slug ?? $this->item_slug_snapshot;
    }

    /**
     * Check if item still exists in database
     */
    public function hasLiveItem(): bool
    {
        return $this->item !== null;
    }
}
