<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMeta extends Model
{
    use HasFactory;

    protected $table = 'user_meta';

    // Allow mass assignment for these fields
    protected $fillable = [
        'user_id',
        'username',
        'interest',
        'bio',
    ];

    // Cast 'interest' as an array
    protected $casts = [
        'interest' => 'array',
    ];
}
