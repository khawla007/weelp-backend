<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Itinerary $itinerary
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLike newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLike newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLike query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLike whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLike whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLike whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLike whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryLike whereUserId($value)
 * @mixin \Eloquent
 */
class ItineraryLike extends Model
{
    protected $fillable = ['itinerary_id', 'user_id'];

    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
