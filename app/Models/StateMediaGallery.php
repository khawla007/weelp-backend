<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateMediaGallery extends Model
{
    use HasFactory;

    protected $table = 'state_media_gallery';

    protected $fillable = [
        'state_id',
        'media_id',
        'is_featured',
    ];

    // Relations
    public function state()
    {
        return $this->belongsTo(State::class);
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
