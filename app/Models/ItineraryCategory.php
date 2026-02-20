<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryCategory extends Model
{
    // protected $table = 'itinerary_category';

    protected $fillable = [
        'itinerary_id', 'category_id'
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
