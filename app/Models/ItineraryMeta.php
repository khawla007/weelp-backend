<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ItineraryMeta extends Model
{
    use HasFactory;

    protected $table = 'itinerary_meta';

    protected $fillable = [
        'itinerary_id',
        'creator_id',
        'user_id',
        'parent_itinerary_id',
        'draft_itinerary_id',
        'status',
        'views_count',
        'likes_count',
        'removal_status',
        'removal_reason',
    ];

    protected $casts = [
        'views_count' => 'integer',
        'likes_count' => 'integer',
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parentItinerary()
    {
        return $this->belongsTo(Itinerary::class, 'parent_itinerary_id');
    }

    public function draftItinerary()
    {
        return $this->belongsTo(Itinerary::class, 'draft_itinerary_id');
    }
}
