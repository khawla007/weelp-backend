<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'avatar',
        'address_line_1',
        'city',
        'state',
        'country',
        'post_code',
        'phone',
        'facebook_url',
        'instagram_url',
        'linkedin_url',
        'myspace_url',
        'pinterest_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function urls()
    {
        return $this->hasMany(UserProfileUrl::class, 'user_profile_id');
    }
}
