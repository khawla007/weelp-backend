<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['name', 'alt_text', 'url', 'file_size', 'width', 'height'];

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
        return $this->hasMany(TransferyMediaGallery::class, 'media_id');
    }

    public function reviews()
    {
        return $this->belongsToMany(Review::class, 'review_media', 'media_id', 'review_id');
    }
    
}
