<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityAvailability extends Model
{
    // protected $table = 'activity_availability';
    protected $fillable = [
        'activity_id',
        'date_based_activity',
        'start_date',
        'end_date',
        'quantity_based_activity',
        'max_quantity',
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
