<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivitySeasonalPricing extends Model {
    use HasFactory;

    protected $table = 'activity_seasonal_pricing';
    protected $fillable = ['activity_id', 'enable_seasonal_pricing', 'season_name', 'season_start', 'season_end', 'season_price'];

    protected $casts = [
        'enable_seasonal_pricing' => 'boolean'
    ];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}
