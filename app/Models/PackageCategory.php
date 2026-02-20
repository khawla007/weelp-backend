<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageCategory extends Model
{
    // protected $table = 'itinerary_category';

    protected $fillable = [
        'package_id', 'category_id'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
