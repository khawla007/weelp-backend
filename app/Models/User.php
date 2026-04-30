<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $role
 * @property string $status
 * @property int $is_creator
 * @property int|null $avatar
 * @property \Illuminate\Support\Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $remember_token
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Commission> $commissions
 * @property-read int|null $commissions_count
 * @property-read \App\Models\UserMeta|null $meta
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PostLike> $postLikes
 * @property-read int|null $post_likes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Post> $posts
 * @property-read int|null $posts_count
 * @property-read \App\Models\UserProfile|null $profile
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read \App\Models\CreatorApplication|null $creatorApplication
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\CreatorApplication> $creatorApplications
 * @property-read int|null $creator_applications_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Itinerary> $creatorItineraries
 * @property-read int|null $creator_itineraries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Itinerary> $userItineraries
 * @property-read int|null $user_itineraries_count
 * @property-read \App\Models\Media|null $avatarMedia
 *
 * @method static \Database\Factories\UserFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereAvatar($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereEmailVerifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereIsCreator($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User wherePassword($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRememberToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    // Role constants
    const ROLE_SUPER_ADMIN = 'super_admin';

    const ROLE_ADMIN = 'admin';

    const ROLE_CUSTOMER = 'customer';

    // Status constants
    const STATUS_ACTIVE = 'active';

    const STATUS_INACTIVE = 'inactive';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'locked_until' => 'datetime',
            'failed_login_attempts' => 'integer',
        ];
    }

    /**
     * The "booted" method of the model.
     *
     * Set default role if not provided during creation.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->role)) {
                $user->role = self::ROLE_CUSTOMER; // Default to 'customer'
            }

            if (empty($user->status)) {
                $user->status = self::STATUS_ACTIVE; // Default status to 'active'
            }
        });

    }

    /**
     * Get the identifier that will be stored in the JWT claim.
     *
     * @return mixed
     */
    public function getAuthIdentifierName()
    {
        return 'id'; // This is the default column for identifying a user
    }

    /**
     * Get the identifier for the JWT claim.
     *
     * @return mixed
     */
    public function getAuthIdentifier()
    {
        return $this->getKey(); // Returns the user id
    }

    /**
     * Get the password for the JWT authentication.
     *
     * @return string
     */
    public function getAuthPassword()
    {
        return $this->password; // Default password field
    }

    /**
     * Get custom claims for the JWT (optional).
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return []; // You can add custom claims here if needed
    }

    /**
     * Get the key for the JWT (optional).
     *
     * @return string
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // This will return the user id for JWT identification
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    public function meta(): HasOne
    {
        return $this->hasOne(UserMeta::class, 'user_id', 'id');
    }

    public function avatarMedia(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'avatar');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'user_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class, 'creator_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(Commission::class, 'creator_id');
    }

    public function postLikes(): HasMany
    {
        return $this->hasMany(PostLike::class);
    }

    public function creatorApplication(): HasOne
    {
        return $this->hasOne(CreatorApplication::class)->latest();
    }

    public function creatorApplications(): HasMany
    {
        return $this->hasMany(CreatorApplication::class);
    }

    public function creatorItineraries(): HasMany
    {
        return $this->hasMany(Itinerary::class, 'creator_id');
    }

    public function userItineraries(): HasMany
    {
        return $this->hasMany(Itinerary::class, 'user_id');
    }
}
