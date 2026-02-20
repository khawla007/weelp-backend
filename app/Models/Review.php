<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'item_type',
        'item_id',
        'rating',
        'review_text',
        'media_gallery',
        'status',
    ];

    protected $casts = [
        'media_gallery' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Polymorphic relation
    public function item()
    {
        return $this->morphTo(__FUNCTION__, 'item_type', 'item_id');
    }

    // Gallery images → directly from media IDs stored in JSON
    public function medias()
    {
        return Media::whereIn('id', $this->media_gallery ?? [])->get();
    }
}
