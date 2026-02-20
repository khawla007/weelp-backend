<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CitySeo extends Model
{
    use HasFactory;

    protected $table = 'city_seo';
    protected $fillable = [
        'city_id', 'meta_title', 'meta_description', 'keywords', 
        'og_image_url', 'canonical_url', 'schema_type', 'schema_data'
    ];

    // protected $casts = [
    //     'schema_data' => 'json'
    // ];

    // Store JSON as raw JSON (Not as escaped string)
    public function setSchemaDataAttribute($value)
    {
        $this->attributes['schema_data'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // Retrieve JSON as array
    public function getSchemaDataAttribute($value)
    {
        return json_decode($value, true);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
