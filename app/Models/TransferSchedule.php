<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferSchedule extends Model
{
    use HasFactory;

    protected $table = 'transfer_schedules';

    protected $fillable = [
        'transfer_id',
        'is_vendor',
        'availability_type',
        'available_days',
        'time_slots',
        'blackout_dates',
        'minimum_lead_time',
        'maximum_passengers',
    ];

    protected $casts = [
        'is_vendor' => 'boolean',
        'available_days' => 'array',
        'time_slots' => 'array',
        'blackout_dates' => 'array',
        'minimum_lead_time' => 'integer',
        'maximum_passengers' => 'integer',
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
