<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLastMinuteDiscount extends Model {
    use HasFactory;

    protected $fillable = ['activity_id', 'enabled', 'days_before_start', 'discount_amount', 'discount_type'];

    protected $casts = [
        'enabled' => 'boolean'
    ];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}
