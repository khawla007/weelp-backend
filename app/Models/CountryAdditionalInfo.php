<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountryAdditionalInfo extends Model
{
    use HasFactory;

    protected $fillable = [
        'country_id',
        'title',
        'content',
    ];

    public function country() {
        return $this->belongsTo(Country::class);
    }
}
