<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property int $tag_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Itinerary $itinerary
 * @property-read \App\Models\Tag $tag
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTag whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTag whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryTag whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ItineraryTag extends Model
{
    protected $fillable = [
        'itinerary_id', 'tag_id'
    ];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}
