<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class TransferZoneLocation extends Model
{
    protected $fillable = [
        'transfer_zone_id',
        'locatable_type',
        'locatable_id',
    ];

    public function zone()
    {
        return $this->belongsTo(TransferZone::class, 'transfer_zone_id');
    }

    public function locatable(): MorphTo
    {
        return $this->morphTo();
    }
}
