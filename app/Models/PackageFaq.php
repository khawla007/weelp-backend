<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageFaq extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        // 'question_number',
        'question',
        'answer',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
