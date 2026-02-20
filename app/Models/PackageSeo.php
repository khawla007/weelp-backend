<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageSeo extends Model
{

    protected $table = 'package_seo';

    protected $fillable = [
        'package_id', 'meta_title', 'meta_description', 
        'keywords', 'og_image_url', 'canonical_url', 
        'schema_type', 'schema_data'
    ];

    protected $casts = [
        'schema_data' => 'array'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
