<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $region_id
 * @property int $country_id
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegionCountry newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegionCountry newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegionCountry query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegionCountry whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegionCountry whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RegionCountry whereRegionId($value)
 * @mixin \Eloquent
 */
class RegionCountry extends Model
{
    protected $table = 'region_country';
    public $timestamps = false;
}
