<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


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
    const STATUS_PENDING = 'pending';


    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Add role here
        'status',
        'email_verified_at'
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
                $user->status = self::STATUS_PENDING; // Default status to 'pending'
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


    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    public function meta()
    {
        return $this->hasOne(UserMeta::class, 'user_id', 'id');
    }

    public function avatarMedia()
    {
        return $this->belongsTo(Media::class, 'avatar');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'user_id');
    }
}
