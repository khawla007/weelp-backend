<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryTransfer extends Model
{
    protected $fillable = [
        'schedule_id', 'transfer_id', 'start_time', 'end_time', 
        'notes', 'price', 'included', 
        'pickup_location', 'dropoff_location', 'pax'
    ];
    protected $casts = [
        'included' => 'boolean'
    ];
    public function schedule()
    {
        return $this->belongsTo(ItinerarySchedule::class, 'schedule_id');
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
