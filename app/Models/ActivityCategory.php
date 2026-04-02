<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $activity_id
 * @property int $category_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Activity $activity
 * @property-read \App\Models\Category $category
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCategory whereActivityId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ActivityCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ActivityCategory extends Model
{
    use HasFactory;

    protected $fillable = ['activity_id', 'category_id'];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
