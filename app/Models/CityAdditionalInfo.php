<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $city_id
 * @property string $title
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\City $city
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityAdditionalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityAdditionalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityAdditionalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityAdditionalInfo whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityAdditionalInfo whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityAdditionalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityAdditionalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityAdditionalInfo whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityAdditionalInfo whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class CityAdditionalInfo extends Model
{
    use HasFactory;

    // protected $table = 'city_additional_info';
    protected $fillable = [
        'city_id', 'title', 'content',
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }
}
