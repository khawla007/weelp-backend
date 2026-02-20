<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StateFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'state_id',
        'question_number',
        'question',
        'answer',
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
