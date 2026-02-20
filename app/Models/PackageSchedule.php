<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
