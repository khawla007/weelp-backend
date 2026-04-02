<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $place_id
 * @property int $question_number
 * @property string $question
 * @property string $answer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Place $place
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceFaq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceFaq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceFaq query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceFaq whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceFaq whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceFaq whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceFaq wherePlaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceFaq whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceFaq whereQuestionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceFaq whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PlaceFaq extends Model {
    use HasFactory;

    protected $fillable = [
        'place_id',
        'question_number',
        'question',
        'answer',
    ];

    public function place() {
        return $this->belongsTo(Place::class);
    }
}
