<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $base_pricing_id
 * @property string $name
 * @property numeric $regular_price
 * @property numeric $sale_price
 * @property int $max_guests
 * @property string|null $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\PackageBasePricing $basePricing
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackagePriceVariation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackagePriceVariation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackagePriceVariation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackagePriceVariation whereBasePricingId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackagePriceVariation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackagePriceVariation whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackagePriceVariation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackagePriceVariation whereMaxGuests($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackagePriceVariation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackagePriceVariation whereRegularPrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackagePriceVariation whereSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackagePriceVariation whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class PackagePriceVariation extends Model
{
    protected $fillable = [
        'base_pricing_id', 'name', 'regular_price', 'sale_price',
        'max_guests', 'description',
    ];

    public function basePricing()
    {
        return $this->belongsTo(PackageBasePricing::class, 'base_pricing_id');
    }
}
