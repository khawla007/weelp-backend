<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $activity_id
 * @property int $addon_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 * @property-read \App\Models\Addon $addon
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAddon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAddon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAddon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAddon whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAddon whereAddonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAddon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAddon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAddon whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ActivityAddon extends Model
{
    protected $table = 'activity_addons';

    protected $fillable = [
        'activity_id',
        'addon_id',
    ];

    // Relations
    public function activity(): BelongsTo
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }
}
