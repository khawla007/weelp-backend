<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityPromoCode extends Model {
    use HasFactory;

    protected $fillable = ['activity_id', 'promo_code', 'max_uses', 'discount_amount', 'discount_type', 'valid_from', 'valid_to'];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }
}
