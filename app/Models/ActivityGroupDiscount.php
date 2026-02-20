<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityGroupDiscount extends Model {
    use HasFactory;

    protected $fillable = ['activity_id', 'min_people', 'discount_amount', 'discount_type'];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}
