<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferZonePrice extends Model
{
    use HasFactory;

    protected $fillable = [
        'from_zone_id',
        'to_zone_id',
        'base_price',
        'currency',
    ];

    protected $casts = [
        'base_price' => 'decimal:2',
    ];

    public function fromZone()
    {
        return $this->belongsTo(TransferZone::class, 'from_zone_id');
    }

    public function toZone()
    {
        return $this->belongsTo(TransferZone::class, 'to_zone_id');
    }
}
