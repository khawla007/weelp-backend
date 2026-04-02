<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $city_id
 * @property int $media_id
 * @property int $is_featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\City $city
 * @property-read \App\Models\Media $media
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityMediaGallery featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityMediaGallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityMediaGallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityMediaGallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityMediaGallery whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityMediaGallery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityMediaGallery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityMediaGallery whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityMediaGallery whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityMediaGallery whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CityMediaGallery extends Model
{
    use HasFactory;

    protected $table = 'city_media_gallery';

    protected $fillable = [
        'city_id',
        'media_id',
        'is_featured',
    ];

    // Relations
    public function city()
    {
        return $this->belongsTo(City::class);
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
