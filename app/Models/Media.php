<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $name
 * @property string|null $alt_text
 * @property string $url
 * @property int|null $file_size
 * @property int|null $width
 * @property int|null $height
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityMediaGallery> $activityMedia
 * @property-read int|null $activity_media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Blog> $blogs
 * @property-read int|null $blogs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CityMediaGallery> $cityMedia
 * @property-read int|null $city_media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CountryMediaGallery> $countryMedia
 * @property-read int|null $country_media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryMediaGallery> $itineraryMedia
 * @property-read int|null $itinerary_media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageMediaGallery> $packageMedia
 * @property-read int|null $package_media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PlaceMediaGallery> $placeMedia
 * @property-read int|null $place_media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\StateMediaGallery> $stateMedia
 * @property-read int|null $state_media_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\TransferMediaGallery> $transferMedia
 * @property-read int|null $transfer_media_count
 * @property-read \App\Models\User|null $userAvatar
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereAltText($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereFileSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereHeight($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Media whereWidth($value)
 * @mixin \Eloquent
 */
class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['name', 'alt_text', 'url', 'file_size', 'width', 'height'];

    // Note: 'url' column stores relative paths; getUrlAttribute accessor converts to full URLs automatically

    /**
     * Get the full URL for the media file.
     * Converts stored relative path to full URL using Storage facade.
     */
    public function getUrlAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Legacy data: already a full URL (before migration cleanup)
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        return Storage::disk('minio')->url($value);
    }

    /**
     * Boot method to register model events
     */
    protected static function boot()
    {
        parent::boot();

        // Auto-increment duplicate names before creating
        static::creating(function ($media) {
            $media->name = self::getUniqueName($media->name);
        });
    }

    /**
     * Generate a unique name by appending counter if duplicate exists
     * Examples:
     *   "China - Image 1" -> (first) -> "China - Image 1"
     *   "China - Image 1" -> (second) -> "China - Image 1-1"
     *   "China - Image 1" -> (third) -> "China - Image 1-2"
     *   "China - Image 1-1" -> (next) -> "China - Image 1-2"
     *
     * @param string $name
     * @return string
     */
    private static function getUniqueName(string $name): string
    {
        $originalName = $name;
        $counter = 0;
        $uniqueName = $name;

        // Keep checking until we find a name that doesn't exist
        while (self::nameExists($uniqueName)) {
            $counter++;

            // Extract base name and existing counter
            $pattern = '/^(.+?)-(\d+)$/';
            if (preg_match($pattern, $originalName, $matches)) {
                // Already has a counter (e.g., "China - Image 1-1")
                $baseName = $matches[1];
                $uniqueName = $baseName . '-' . $counter;
            } else {
                // No counter yet (e.g., "China - Image 1")
                $uniqueName = $originalName . '-' . $counter;
            }
        }

        return $uniqueName;
    }

    /**
     * Check if a name already exists in the media table
     *
     * @param string $name
     * @return bool
     */
    private static function nameExists(string $name): bool
    {
        return self::where('name', $name)->exists();
    }

    public function userAvatar()
    {
        return $this->hasOne(User::class, 'avatar');
    }

    public function countryMedia()
    {
        return $this->hasMany(CountryMediaGallery::class, 'media_id');
    }

    public function stateMedia()
    {
        return $this->hasMany(StateMediaGallery::class, 'media_id');
    }

    public function cityMedia()
    {
        return $this->hasMany(CityMediaGallery::class, 'media_id');
    }

    public function placeMedia()
    {
        return $this->hasMany(PlaceMediaGallery::class, 'media_id');
    }
    
    // public function blogs()
    // {
    //     return $this->hasMany(Blog::class, 'featured_image');
    // }
    public function blogs()
    {
        return $this->belongsToMany(Blog::class, 'blog_media_gallery');
    }

    public function itineraryMedia()
    {
        return $this->hasMany(ItineraryMediaGallery::class, 'media_id');
    }

    public function packageMedia()
    {
        return $this->hasMany(PackageMediaGallery::class, 'media_id');
    }

    public function activityMedia()
    {
        return $this->hasMany(ActivityMediaGallery::class, 'media_id');
    }

    public function transferMedia()
    {
        return $this->hasMany(TransferMediaGallery::class, 'media_id');
    }

    public function reviews()
    {
        return $this->belongsToMany(Review::class, 'review_media', 'media_id', 'review_id');
    }
    
}
