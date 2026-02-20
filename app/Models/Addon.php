<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Addon extends Model
{
    protected $fillable = [
        'name',
        'type',
        'description',
        'price',
        'sale_price',
        'price_calculation',
        'active_status',
    ];

    protected $casts = [
        'active_status' => 'boolean',
    ];

    // public function activitiesAddon()
    // {
    //     return $this->belongsToMany(ActivityAddon::class, 'activity_addons', 'addon_id', 'activity_id')->withTimestamps();
    // }
    public function activitiesAddon()
    {
        return $this->hasMany(ActivityAddon::class, 'addon_id');
    }

    public function itinerariesAddon()
    {
        return $this->hasMany(IitineraryAddon::class, 'addon_id');
    }

    public function packagesAddon()
    {
        return $this->hasMany(PackageAddon::class, 'addon_id');
    }
}
