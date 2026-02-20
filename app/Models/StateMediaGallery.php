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
    ];

    // Relations
    public function state()
    {
        return $this->belongsTo(state::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
