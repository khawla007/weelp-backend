<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageActivity extends Model
{
    use HasFactory;

    protected $fillable = [
        'schedule_id',
        'activity_id',
        'start_time',
        'end_time',
        'notes',
        'price',
        'included',
    ];

    protected $casts = [
        'included' => 'boolean'
    ];

    public function schedule()
    {
        return $this->belongsTo(PackageSchedule::class);
    }

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }
}
