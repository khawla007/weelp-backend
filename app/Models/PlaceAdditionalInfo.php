<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceAdditionalInfo extends Model {
    use HasFactory;

    // protected $table = 'place_additional_info';
    protected $fillable = [
        'place_id',
        'title',
        'content',
    ];

    public function place() {
        return $this->belongsTo(Place::class);
    }
}
