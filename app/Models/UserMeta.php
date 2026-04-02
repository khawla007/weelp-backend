<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string|null $username
 * @property array<array-key, mixed>|null $interest
 * @property string|null $bio
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMeta newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMeta newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMeta query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMeta whereBio($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMeta whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMeta whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMeta whereInterest($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMeta whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMeta whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UserMeta whereUsername($value)
 *
 * @mixin \Eloquent
 */
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
