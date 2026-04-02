<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property int $addon_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Addon $addon
 * @property-read \App\Models\Itinerary $itinerary
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAddon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAddon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAddon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAddon whereAddonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAddon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAddon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAddon whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAddon whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ItineraryAddon extends Model
{
    protected $table = 'itinerary_addons';

    protected $fillable = [
        'itinerary_id',
        'addon_id',
    ];

    // Relations
    public function itinerary()
    {
        return $this->belongsTo(Itinerary::class, 'itinerary_id');
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }
}
