<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property int $attribute_id
 * @property string $attribute_value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Attribute $attribute
 * @property-read \App\Models\Itinerary $itinerary
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAttribute whereAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAttribute whereAttributeValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAttribute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAttribute whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryAttribute whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ItineraryAttribute extends Model
{
    protected $fillable = [
        'itinerary_id', 'attribute_id', 'attribute_value',
    ];

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
