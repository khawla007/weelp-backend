<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $activity_id
 * @property string $location_type
 * @property int $city_id
 * @property string|null $location_label
 * @property int|null $duration
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 * @property-read \App\Models\City $city
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLocation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLocation whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLocation whereCityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLocation whereDuration($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLocation whereLocationLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLocation whereLocationType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityLocation whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ActivityLocation extends Model {
    use HasFactory;

    protected $fillable = ['activity_id', 'city_id', 'location_type', 'location_label', 'duration'];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }

    public function city() {
        return $this->belongsTo(City::class, 'city_id');
    }
}
