<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateEvent extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'name',
        'type',
        'date',
        'location',
        'description',
    ];

    protected $casts = [
        'type'     => 'array',
        // 'location' => 'array',
        'date'=> 'date:Y-m-d', 
    ];


    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
