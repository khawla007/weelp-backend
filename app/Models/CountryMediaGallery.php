<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryMediaGallery extends Model
{
    use HasFactory;

    protected $table = 'country_media_gallery';

    protected $fillable = [
        'country_id',
        'media_id',
    ];

    // Relations
    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
