<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CityFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'city_id', 'question_number', 'question', 'answer'
    ];

    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
