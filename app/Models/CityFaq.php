<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $city_id
 * @property int $question_number
 * @property string $question
 * @property string $answer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\City $city
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityFaq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityFaq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityFaq query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityFaq whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityFaq whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityFaq whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityFaq whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityFaq whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityFaq whereQuestionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CityFaq whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CityFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id', 'question_number', 'question', 'answer',
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
