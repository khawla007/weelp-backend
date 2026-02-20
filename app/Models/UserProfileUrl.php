<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfileUrl extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_profile_id',
        'label',
        'url',
    ];

    public function profile()
    {
        return $this->belongsTo(UserProfile::class, 'user_profile_id');
    }
}
