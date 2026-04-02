<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $activity_id
 * @property int $tag_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 * @property-read \App\Models\Tag $tag
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTag whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTag whereTagId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityTag whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ActivityTag extends Model
{
    protected $table = 'activity_tag';

    protected $fillable = [
        'activity_id', 'tag_id',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }

    public function tag(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}
