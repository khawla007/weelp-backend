<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityMediaGallery extends Model
{
    protected $table = 'activity_media_gallery';

    protected $fillable = [
        'activity_id', 'media_id'
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
