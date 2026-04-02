<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string|null $type
 * @property string|null $description
 * @property numeric $price
 * @property numeric|null $sale_price
 * @property string|null $price_calculation
 * @property bool $active_status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityAddon> $activitiesAddon
 * @property-read int|null $activities_addon_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryAddon> $itinerariesAddon
 * @property-read int|null $itineraries_addon_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageAddon> $packagesAddon
 * @property-read int|null $packages_addon_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereActiveStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon wherePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon wherePriceCalculation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereSalePrice($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Addon whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Addon extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'price',
        'sale_price',
        'price_calculation',
        'active_status',
    ];

    protected $casts = [
        'active_status' => 'boolean',
    ];

    // public function activitiesAddon()
    // {
    //     return $this->belongsToMany(ActivityAddon::class, 'activity_addons', 'addon_id', 'activity_id')->withTimestamps();
    // }
    public function activitiesAddon()
    {
        return $this->hasMany(ActivityAddon::class, 'addon_id');
    }

    public function itinerariesAddon()
    {
        return $this->hasMany(ItineraryAddon::class, 'addon_id');
    }

    public function packagesAddon()
    {
        return $this->hasMany(PackageAddon::class, 'addon_id');
    }
}
