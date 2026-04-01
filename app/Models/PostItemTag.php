<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostItemTag extends Model
{
    use HasFactory;

    protected $fillable = ['post_id', 'taggable_id', 'taggable_type'];

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

    public function taggable()
    {
        return $this->morphTo();
    }
}
