<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $type
 * @property string|null $description
 * @property array<array-key, mixed>|null $values
 * @property string|null $default_value
 * @property string $status
 * @property bool $is_featured
 * @property string $taxonomy
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Activity> $activities
 * @property-read int|null $activities_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ActivityAttribute> $activityAttributes
 * @property-read int|null $activity_attributes_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Itinerary> $itineraries
 * @property-read int|null $itineraries_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryAttribute> $itinerariesAttributes
 * @property-read int|null $itineraries_attributes_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute whereDefaultValue($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute whereTaxonomy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Attribute whereValues($value)
 *
 * @mixin \Eloquent
 */
class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'type',
        'description',
        'values',
        'default_value',
        'taxonomy',
        'post_type',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'values' => 'array', // Automatically handles JSON conversion
        'is_featured' => 'boolean',
    ];

    /**
     * Automatically set the slug and taxonomy before saving.
     */
    public static function boot()
    {
        parent::boot();

        static::creating(function ($attribute) {
            $slug = Str::slug($attribute->name, '-');
            $attribute->slug = $slug;
            $attribute->taxonomy = $slug;
        });

        static::updating(function ($attribute) {
            $slug = Str::slug($attribute->name, '-');
            $attribute->slug = $slug;
            $attribute->taxonomy = $slug;
        });
    }

    public function activityAttributes()
    {
        return $this->hasMany(ActivityAttribute::class, 'attribute_id');
    }

    public function activities()
    {
        return $this->hasManyThrough(Activity::class, ActivityAttribute::class, 'attribute_id', 'id', 'id', 'activity_id');
    }

    public function itinerariesAttributes()
    {
        return $this->hasMany(ItineraryAttribute::class, 'attribute_id');
    }

    public function itineraries()
    {
        return $this->hasManyThrough(Itinerary::class, ItineraryAttribute::class, 'attribute_id', 'id', 'id', 'activity_id');
    }
}
