<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $country_id
 * @property int|null $question_number
 * @property string $question
 * @property string $answer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Country $country
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryFaq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryFaq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryFaq query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryFaq whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryFaq whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryFaq whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryFaq whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryFaq whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryFaq whereQuestionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountryFaq whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CountryFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'question_number',
        'question',
        'answer',
    ];

    public function country()
    {
        return $this->belongsTo(Country::class);
    }
}
