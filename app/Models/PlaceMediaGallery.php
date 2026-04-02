<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $place_id
 * @property int $media_id
 * @property int $is_featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Media $media
 * @property-read \App\Models\Place $place
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceMediaGallery featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceMediaGallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceMediaGallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceMediaGallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceMediaGallery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceMediaGallery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceMediaGallery whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceMediaGallery whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceMediaGallery wherePlaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceMediaGallery whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PlaceMediaGallery extends Model
{
    use HasFactory;

    protected $table = 'place_media_gallery';

    protected $fillable = [
        'place_id',
        'media_id',
        'is_featured',
    ];

    // Relations
    public function place()
    {
        return $this->belongsTo(Place::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    // Scopes
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
