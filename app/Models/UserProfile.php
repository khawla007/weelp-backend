<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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

    protected $appends = ['avatar'];

    /**
     * Get the full URL for the avatar.
     * Converts stored relative path to full URL using Storage facade.
     */
    public function getAvatarAttribute($value)
    {
        if (!$value) {
            return null;
        }

        // Legacy data: already a full URL (before migration cleanup)
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        return Storage::disk('minio')->url($value);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function urls()
    {
        return $this->hasMany(UserProfileUrl::class, 'user_profile_id');
    }
}
