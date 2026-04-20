<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $state_id
 * @property int|null $question_number
 * @property string $question
 * @property string $answer
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\State $state
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateFaq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateFaq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateFaq query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateFaq whereAnswer($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateFaq whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateFaq whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateFaq whereQuestion($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateFaq whereQuestionNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateFaq whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateFaq whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class StateFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'question_number',
        'question',
        'answer',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
