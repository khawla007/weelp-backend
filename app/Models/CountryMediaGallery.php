<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $country_id
 * @property int $media_id
 * @property int $is_featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Country $country
 * @property-read \App\Models\Media $media
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryMediaGallery featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryMediaGallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryMediaGallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryMediaGallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryMediaGallery whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryMediaGallery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryMediaGallery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryMediaGallery whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryMediaGallery whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryMediaGallery whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CountryMediaGallery extends Model
{
    use HasFactory;

    protected $table = 'country_media_gallery';

    protected $fillable = [
        'country_id',
        'media_id',
        'is_featured',
    ];

    // Relations
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    // Scopes
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
