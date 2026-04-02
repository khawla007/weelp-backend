<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property int $media_id
 * @property int $is_featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Itinerary $itinerary
 * @property-read \App\Models\Media $media
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryMediaGallery featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryMediaGallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryMediaGallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryMediaGallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryMediaGallery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryMediaGallery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryMediaGallery whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryMediaGallery whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryMediaGallery whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryMediaGallery whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ItineraryMediaGallery extends Model
{
    protected $table = 'itinerary_media_gallery';

    protected $fillable = [
        'itinerary_id', 'media_id', 'is_featured',
    ];

    /**
     * Scope a query to only include featured media.
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
