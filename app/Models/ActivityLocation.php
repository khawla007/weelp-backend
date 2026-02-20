<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLocation extends Model {
    use HasFactory;

    protected $fillable = ['activity_id', 'city_id', 'location_type', 'location_label', 'duration'];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }

    public function city() {
        return $this->belongsTo(City::class, 'city_id');
    }
}
