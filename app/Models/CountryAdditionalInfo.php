<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $country_id
 * @property string $title
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Country $country
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryAdditionalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryAdditionalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryAdditionalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryAdditionalInfo whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryAdditionalInfo whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryAdditionalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryAdditionalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryAdditionalInfo whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryAdditionalInfo whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class CountryAdditionalInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'title',
        'content',
    ];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
