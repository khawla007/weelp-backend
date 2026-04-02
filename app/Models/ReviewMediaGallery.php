<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $review_id
 * @property int $media_id
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Media $media
 * @property-read \App\Models\Review $review
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewMediaGallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewMediaGallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewMediaGallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewMediaGallery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewMediaGallery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewMediaGallery whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewMediaGallery whereReviewId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewMediaGallery whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ReviewMediaGallery whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class ReviewMediaGallery extends Model
{
    protected $table = 'review_media_gallery';

    protected $fillable = ['review_id', 'media_id', 'sort_order'];

    public function review()
    {
        return $this->belongsTo(Review::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class);
    }
}
