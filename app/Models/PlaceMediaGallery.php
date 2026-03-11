<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
