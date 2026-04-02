<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $base_pricing_id
 * @property string $name
 * @property numeric $regular_price
 * @property numeric|null $sale_price
 * @property int $max_guests
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\ItineraryBasePricing $basePricing
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryPriceVariation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryPriceVariation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryPriceVariation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryPriceVariation whereBasePricingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryPriceVariation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryPriceVariation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryPriceVariation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryPriceVariation whereMaxGuests($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryPriceVariation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryPriceVariation whereRegularPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryPriceVariation whereSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryPriceVariation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ItineraryPriceVariation extends Model
{
    protected $fillable = [
        'base_pricing_id', 'name', 'regular_price', 'sale_price',
        'max_guests', 'description',
    ];

    public function basePricing(): BelongsTo
    {
        return $this->belongsTo(ItineraryBasePricing::class, 'base_pricing_id');
    }
}
