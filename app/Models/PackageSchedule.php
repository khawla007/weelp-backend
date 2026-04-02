<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $package_id
 * @property int $day
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageActivity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageItinerary> $itineraries
 * @property-read int|null $itineraries_count
 * @property-read \App\Models\Package $package
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PackageTransfer> $transfers
 * @property-read int|null $transfers_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSchedule newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSchedule newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSchedule query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSchedule whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSchedule whereDay($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSchedule whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSchedule wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageSchedule whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class PackageSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'day',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function activities()
    {
        return $this->hasMany(PackageActivity::class, 'schedule_id');
    }

    public function transfers()
    {
        return $this->hasMany(PackageTransfer::class, 'schedule_id');
    }

    public function itineraries()
    {
        return $this->hasMany(PackageItinerary::class, 'schedule_id');
    }

}
