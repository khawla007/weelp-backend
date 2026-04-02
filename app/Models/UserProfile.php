<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $avatar
 * @property string|null $address_line_1
 * @property string|null $city
 * @property string|null $state
 * @property string|null $country
 * @property string|null $post_code
 * @property string|null $phone
 * @property string|null $facebook_url
 * @property string|null $instagram_url
 * @property string|null $linkedin_url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\UserProfileUrl> $urls
 * @property-read int|null $urls_count
 * @property-read \App\Models\User $user
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereAddressLine1($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereFacebookUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereInstagramUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereLinkedinUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePostCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereUserId($value)
 *
 * @mixin \Eloquent
 */
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
        if (! $value) {
            return null;
        }

        // Legacy data: already a full URL (before migration cleanup)
        if (str_starts_with($value, 'http')) {
            return $value;
        }

        return Storage::disk('minio')->url($value);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function urls(): HasMany
    {
        return $this->hasMany(UserProfileUrl::class, 'user_profile_id');
    }
}
