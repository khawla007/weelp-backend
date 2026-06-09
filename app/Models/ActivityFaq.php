<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Activity|null $activity
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFaq newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFaq newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityFaq query()
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ActivityFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'activity_id',
        'question_number',
        'question',
        'answer',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
