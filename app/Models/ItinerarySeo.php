<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property string $meta_title
 * @property string|null $meta_description
 * @property string|null $keywords
 * @property string|null $og_image_url
 * @property string|null $canonical_url
 * @property string|null $schema_type
 * @property array<array-key, mixed>|null $schema_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Itinerary $itinerary
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo whereCanonicalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo whereKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo whereOgImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo whereSchemaData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo whereSchemaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItinerarySeo whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ItinerarySeo extends Model
{
    protected $table = 'itinerary_seo';

    protected $fillable = [
        'itinerary_id', 'meta_title', 'meta_description',
        'keywords', 'og_image_url', 'canonical_url',
        'schema_type', 'schema_data',
    ];

    protected $casts = [
        'schema_data' => 'array',
    ];

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }
}
