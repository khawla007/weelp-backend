<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $email
 * @property string $gender
 * @property string $instagram
 * @property string $phone
 * @property string|null $youtube
 * @property string|null $facebook
 * @property string $status
 * @property string|null $admin_notes
 * @property int|null $reviewed_by
 * @property \Illuminate\Support\Carbon|null $reviewed_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User|null $reviewer
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereAdminNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereFacebook($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereGender($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereInstagram($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereReviewedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereReviewedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CreatorApplication whereYoutube($value)
 * @mixin \Eloquent
 */
class CreatorApplication extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'email',
        'gender',
        'instagram',
        'phone',
        'youtube',
        'facebook',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
