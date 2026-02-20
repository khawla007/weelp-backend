<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Region extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'type', 'description', 'image_url'];

    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    public function countries()
    {
        // return $this->belongsToMany(Country::class, 'region_country');
        return $this->belongsToMany(Country::class, 'region_country', 'region_id', 'country_id');
    }
}
