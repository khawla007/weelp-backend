<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $state_id
 * @property numeric|null $latitude
 * @property numeric|null $longitude
 * @property string|null $capital_city
 * @property int|null $population
 * @property string|null $currency
 * @property string|null $timezone
 * @property array<array-key, mixed>|null $language
 * @property array<array-key, mixed>|null $local_cuisine
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\State $state
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail whereCapitalCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail whereLanguage($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail whereLatitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail whereLocalCuisine($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail whereLongitude($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail wherePopulation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail whereTimezone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateLocationDetail whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class StateLocationDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id', 'latitude', 'longitude', 'capital_city', 'population', 'currency', 'timezone', 'language', 'local_cuisine',
    ];

    protected $casts = [
        'language' => 'array',
        'local_cuisine' => 'array',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
