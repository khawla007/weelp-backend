<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TransferRoute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'origin_type',
        'origin_id',
        'destination_type',
        'destination_id',
        'from_zone_id',
        'to_zone_id',
        'distance_km',
        'duration_minutes',
        'is_active',
        'is_popular',
    ];

    protected $casts = [
        'is_active'        => 'boolean',
        'is_popular'       => 'boolean',
        'distance_km'      => 'decimal:2',
        'duration_minutes' => 'integer',
    ];

    public function origin(): MorphTo
    {
        return $this->morphTo();
    }

    public function destination(): MorphTo
    {
        return $this->morphTo();
    }

    public function fromZone()
    {
        return $this->belongsTo(TransferZone::class, 'from_zone_id');
    }

    public function toZone()
    {
        return $this->belongsTo(TransferZone::class, 'to_zone_id');
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class, 'transfer_route_id');
    }

    /**
     * Resolve base price from the zone pricing matrix for this route's
     * from_zone_id × to_zone_id pair. Returns null when zones unset or no cell.
     */
    public function resolvedPrice(): ?TransferZonePrice
    {
        if (! $this->from_zone_id || ! $this->to_zone_id) {
            return null;
        }

        return TransferZonePrice::query()
            ->where('from_zone_id', $this->from_zone_id)
            ->where('to_zone_id', $this->to_zone_id)
            ->first();
    }
}
