<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageMediaGallery extends Model
{

    protected $table = 'package_media_gallery';

    protected $fillable = [
        'package_id', 'media_id'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
