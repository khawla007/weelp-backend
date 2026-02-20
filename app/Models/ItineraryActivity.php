<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ItineraryActivity extends Model
{
    protected $fillable = [
        'schedule_id', 'activity_id', 'start_time', 'end_time', 
        'notes', 'price', 'included'
    ];

    protected $casts = [
        'included' => 'boolean'
    ];
    
    public function schedule()
    {
        return $this->belongsTo(ItinerarySchedule::class, 'schedule_id');
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
