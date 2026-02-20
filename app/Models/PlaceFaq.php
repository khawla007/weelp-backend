<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceFaq extends Model {
    use HasFactory;

    protected $fillable = [
        'place_id',
        'question_number',
        'question',
        'answer',
    ];

    public function place() {
        return $this->belongsTo(Place::class);
    }
}
