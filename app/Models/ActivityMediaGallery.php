<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $activity_id
 * @property int $media_id
 * @property int $is_featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 * @property-read \App\Models\Media $media
 *
 * @method static Builder<static>|ActivityMediaGallery featured()
 * @method static Builder<static>|ActivityMediaGallery newModelQuery()
 * @method static Builder<static>|ActivityMediaGallery newQuery()
 * @method static Builder<static>|ActivityMediaGallery query()
 * @method static Builder<static>|ActivityMediaGallery whereActivityId($value)
 * @method static Builder<static>|ActivityMediaGallery whereCreatedAt($value)
 * @method static Builder<static>|ActivityMediaGallery whereId($value)
 * @method static Builder<static>|ActivityMediaGallery whereIsFeatured($value)
 * @method static Builder<static>|ActivityMediaGallery whereMediaId($value)
 * @method static Builder<static>|ActivityMediaGallery whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ActivityMediaGallery extends Model
{
    protected $table = 'activity_media_gallery';

    protected $fillable = [
        'activity_id', 'media_id', 'is_featured',
    ];

    /**
     * Scope a query to only include featured media.
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
