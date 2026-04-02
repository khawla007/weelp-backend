<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $country_id
 * @property string $name
 * @property array<array-key, mixed>|null $type
 * @property \Illuminate\Support\Carbon|null $date
 * @property string $location
 * @property string $description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Country $country
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryEvent newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryEvent newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryEvent query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryEvent whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryEvent whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryEvent whereDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryEvent whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryEvent whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryEvent whereLocation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryEvent whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryEvent whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryEvent whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CountryEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'name',
        'type',
        'date',
        'location',
        'description',
    ];

    protected $casts = [
        'type' => 'array',
        // 'location' => 'array',
        'date' => 'date:Y-m-d',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
