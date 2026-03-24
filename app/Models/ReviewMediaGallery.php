<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReviewMediaGallery extends Model
{
    protected $table = 'review_media_gallery';

    protected $fillable = ['review_id', 'media_id', 'sort_order'];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
