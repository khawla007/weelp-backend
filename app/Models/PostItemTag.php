<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * @property int $id
 * @property int $post_id
 * @property int $taggable_id
 * @property string $taggable_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Post $post
 * @property-read Model|\Eloquent $taggable
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItemTag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItemTag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItemTag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItemTag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItemTag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItemTag wherePostId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItemTag whereTaggableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItemTag whereTaggableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PostItemTag whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class PostItemTag extends Model
{
    use HasFactory;

    protected $fillable = ['post_id', 'taggable_id', 'taggable_type'];

    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }
}
