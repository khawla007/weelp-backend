<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $place_id
 * @property string $title
 * @property string $content
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Place $place
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceAdditionalInfo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceAdditionalInfo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceAdditionalInfo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceAdditionalInfo whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceAdditionalInfo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceAdditionalInfo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceAdditionalInfo wherePlaceId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceAdditionalInfo whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PlaceAdditionalInfo whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class PlaceAdditionalInfo extends Model
{
    use HasFactory;

    // protected $table = 'place_additional_info';
    protected $fillable = [
        'place_id',
        'title',
        'content',
    ];

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
