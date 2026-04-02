<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $state_id
 * @property int $media_id
 * @property int $is_featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Media $media
 * @property-read \App\Models\State $state
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateMediaGallery featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateMediaGallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateMediaGallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateMediaGallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateMediaGallery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateMediaGallery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateMediaGallery whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateMediaGallery whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateMediaGallery whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateMediaGallery whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class StateMediaGallery extends Model
{
    use HasFactory;

    protected $table = 'state_media_gallery';

    protected $fillable = [
        'state_id',
        'media_id',
        'is_featured',
    ];

    // Relations
    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }

    // Scopes
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }
}
