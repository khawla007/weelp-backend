<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property string $currency
 * @property string $availability
 * @property string|null $start_date
 * @property string|null $end_date
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryBlackoutDate> $blackoutDates
 * @property-read int|null $blackout_dates_count
 * @property-read \App\Models\Itinerary $itinerary
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryPriceVariation> $variations
 * @property-read int|null $variations_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBasePricing newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBasePricing newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBasePricing query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBasePricing whereAvailability($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBasePricing whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBasePricing whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBasePricing whereEndDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBasePricing whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBasePricing whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBasePricing whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryBasePricing whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ItineraryBasePricing extends Model
{
    protected $table = 'itinerary_base_pricing';

    protected $fillable = [
        'itinerary_id', 'currency', 'availability',
        'start_date', 'end_date',
    ];

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function variations(): HasMany
    {
        return $this->hasMany(ItineraryPriceVariation::class, 'base_pricing_id');
    }

    public function blackoutDates(): HasMany
    {
        return $this->hasMany(ItineraryBlackoutDate::class, 'base_pricing_id');
    }
}
