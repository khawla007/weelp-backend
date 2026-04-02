<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_profile_id
 * @property string $label
 * @property string $url
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\UserProfile $profile
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfileUrl newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfileUrl newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfileUrl query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfileUrl whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfileUrl whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfileUrl whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfileUrl whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfileUrl whereUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserProfileUrl whereUserProfileId($value)
 *
 * @mixin \Eloquent
 */
class UserProfileUrl extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_profile_id',
        'label',
        'url',
    ];

    public function profile(): BelongsTo
    {
        return $this->belongsTo(UserProfile::class, 'user_profile_id');
    }
}
