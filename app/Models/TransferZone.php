<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function cities()
    {
        return $this->morphedByMany(City::class, 'locatable', 'transfer_zone_locations')
            ->withTimestamps();
    }

    public function places()
    {
        return $this->morphedByMany(Place::class, 'locatable', 'transfer_zone_locations')
            ->withTimestamps();
    }

    public function pricesFrom()
    {
        return $this->hasMany(TransferZonePrice::class, 'from_zone_id');
    }

    public function pricesTo()
    {
        return $this->hasMany(TransferZonePrice::class, 'to_zone_id');
    }

    public function routesFrom()
    {
        return $this->hasMany(TransferRoute::class, 'from_zone_id');
    }

    public function routesTo()
    {
        return $this->hasMany(TransferRoute::class, 'to_zone_id');
    }
}
