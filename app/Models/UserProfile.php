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
 * @property string|null $gender
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile wherePostCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereState($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfile whereUserId($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
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
        'gender',
    ];

    protected $appends = [];

    /**
     * Resolve the stored avatar path to a browser-safe URL.
     *
     * Avatars are served through the app's media proxy (`/api/media/{path}`)
     * so the URL works regardless of which host the browser is on. The MinIO
     * endpoint is reachable from the server but not always from LAN clients.
     */
    public function getAvatarAttribute($value)
    {
        if (! $value) {
            return null;
        }

        // Legacy rows that stored an absolute MinIO URL: strip the host/bucket
        // and serve through the same proxy so LAN clients can resolve it.
        if (str_starts_with($value, 'http')) {
            $path = ltrim(parse_url($value, PHP_URL_PATH) ?? '', '/');
            $bucket = config('filesystems.disks.minio.bucket');
            if ($bucket && str_starts_with($path, "{$bucket}/")) {
                $path = substr($path, strlen($bucket) + 1);
            }

            return $path !== '' ? '/api/media/'.$path : null;
        }

        return '/api/media/'.ltrim($value, '/');
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
