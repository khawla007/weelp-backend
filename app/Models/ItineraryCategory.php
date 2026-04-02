<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $itinerary_id
 * @property int $category_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Category $category
 * @property-read \App\Models\Itinerary $itinerary
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryCategory whereCategoryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryCategory whereItineraryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ItineraryCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ItineraryCategory extends Model
{
    // protected $table = 'itinerary_category';

    protected $fillable = [
        'itinerary_id', 'category_id',
    ];

    public function itinerary(): BelongsTo
    {
        return $this->belongsTo(Itinerary::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }
}
