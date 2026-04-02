<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property string $type
 * @property string $title
 * @property string|null $description
 * @property bool $included
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Itinerary $itinerary
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInclusionExclusion newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInclusionExclusion newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInclusionExclusion query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInclusionExclusion whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInclusionExclusion whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInclusionExclusion whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInclusionExclusion whereIncluded($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInclusionExclusion whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInclusionExclusion whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInclusionExclusion whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryInclusionExclusion whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ItineraryInclusionExclusion extends Model
{
    protected $table = 'itinerary_inclusions_exclusions';

    protected $fillable = [
        'itinerary_id', 'type', 'title',
        'description', 'included',
    ];

    protected $casts = [
        'included' => 'boolean',
    ];

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }
}
