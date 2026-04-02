<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $transfer_id
 * @property int $media_id
 * @property int $is_featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Media $media
 * @property-read \App\Models\Transfer $transfer
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferMediaGallery featured()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferMediaGallery newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferMediaGallery newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferMediaGallery query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferMediaGallery whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferMediaGallery whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferMediaGallery whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferMediaGallery whereMediaId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferMediaGallery whereTransferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferMediaGallery whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class TransferMediaGallery extends Model
{
    use HasFactory;

    protected $table = 'transfer_media_gallery';

    protected $fillable = [
        'transfer_id',
        'media_id',
        'is_featured',
    ];

    /**
     * Scope to get featured media only
     */
    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    // Relationship with Transfer
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }

    public function media(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}
