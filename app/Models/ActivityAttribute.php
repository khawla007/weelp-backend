<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityAttribute extends Model {
    use HasFactory;

    protected $table = 'activity_attribute';
    protected $fillable = ['activity_id', 'attribute_id', 'attribute_value'];

    public function activity() {
        return $this->belongsTo(Activity::class);
    }

    public function attribute() {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
