<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityMediaGallery extends Model
{
    use HasFactory;

    protected $table = 'city_media_gallery';

    protected $fillable = [
        'city_id',
        'media_id',
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
}
