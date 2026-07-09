<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $activity_id
 * @property string $type
 * @property string $title
 * @property string|null $description
 * @property bool $included
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityInclusionExclusion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityInclusionExclusion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityInclusionExclusion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityInclusionExclusion whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityInclusionExclusion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityInclusionExclusion whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityInclusionExclusion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityInclusionExclusion whereIncluded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityInclusionExclusion whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityInclusionExclusion whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityInclusionExclusion whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ActivityInclusionExclusion extends Model
{
    protected $table = 'activity_inclusions_exclusions';

    protected $fillable = [
        'activity_id', 'type', 'title',
        'description', 'included',
    ];

    protected $casts = [
        'included' => 'boolean',
    ];

    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class);
    }
}
