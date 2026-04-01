<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id', 'media_id', 'caption', 'likes_count', 'shares_count', 'status',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    public function taggedItems()
    {
        return $this->hasMany(PostItemTag::class);
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function isLikedBy($userId)
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }
}
