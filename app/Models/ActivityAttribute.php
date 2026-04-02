<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $activity_id
 * @property int $attribute_id
 * @property string $attribute_value
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 * @property-read \App\Models\Attribute $attribute
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAttribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAttribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAttribute query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAttribute whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAttribute whereAttributeId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAttribute whereAttributeValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAttribute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAttribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityAttribute whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ActivityAttribute extends Model
{
    use HasFactory;

    protected $table = 'activity_attribute';

    protected $fillable = ['activity_id', 'attribute_id', 'attribute_value'];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function attribute()
    {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
