<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $item_type
 * @property bool $featured_itinerary
 * @property bool $private_itinerary
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * Delegated via itinerary_meta:
 * @property int|null $creator_id
 * @property int|null $user_id
 * @property int|null $parent_itinerary_id
 * @property int|null $draft_itinerary_id
 * @property string|null $status
 * @property int $views_count
 * @property int $likes_count
 * @property string|null $removal_status
 * @property string|null $removal_reason
 *
 * @property-read \App\Models\ItineraryMeta|null $meta
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryAddon> $addons
 * @property-read int|null $addons_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryAttribute> $attributes
 * @property-read int|null $attributes_count
 * @property-read \App\Models\ItineraryAvailability|null $availability
 * @property-read \App\Models\ItineraryBasePricing|null $basePricing
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryCategory> $categories
 * @property-read int|null $categories_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryInclusionExclusion> $inclusionsExclusions
 * @property-read int|null $inclusions_exclusions_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryLocation> $locations
 * @property-read int|null $locations_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryMediaGallery> $mediaGallery
 * @property-read int|null $media_gallery_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> $orders
 * @property-read int|null $orders_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\PostItemTag> $postTags
 * @property-read int|null $post_tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Review> $reviews
 * @property-read int|null $reviews_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItinerarySchedule> $schedules
 * @property-read int|null $schedules_count
 * @property-read \App\Models\ItinerarySeo|null $seo
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryTag> $tags
 * @property-read int|null $tags_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\ItineraryLike> $likes
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Itinerary> $copies
 * @property-read int|null $copies_count
 * @property-read \App\Models\User|null $creator
 * @property-read \App\Models\User|null $owner
 * @property-read Itinerary|null $parentItinerary
 * @property-read Itinerary|null $draftItinerary
 */
class Itinerary extends Model
{
    use HasFactory;

    protected $table = 'itineraries';

    protected $fillable = [
        'name', 'slug', 'description', 'featured_itinerary', 'private_itinerary',
    ];

    protected $with = ['meta'];

    protected $casts = [
        'featured_itinerary' => 'boolean',
        'private_itinerary' => 'boolean',
    ];

    // Delegated meta attributes
    protected array $metaAttributes = [
        'creator_id', 'user_id', 'parent_itinerary_id', 'draft_itinerary_id',
        'status', 'views_count', 'likes_count', 'removal_status', 'removal_reason',
    ];

    // ─── Meta Relationship ───────────────────────────────────────────

    public function meta()
    {
        return $this->hasOne(ItineraryMeta::class);
    }

    // ─── Accessor / Mutator Delegation ───────────────────────────────

    public function getAttribute($key)
    {
        if (in_array($key, $this->metaAttributes) && !parent::getAttribute($key)) {
            return $this->meta?->$key;
        }
        return parent::getAttribute($key);
    }

    public function fill(array $attributes)
    {
        $metaAttrs = array_intersect_key($attributes, array_flip($this->metaAttributes));
        $itinAttrs = array_diff_key($attributes, array_flip($this->metaAttributes));

        if (!empty($metaAttrs)) {
            if (!$this->meta) {
                $this->setRelation('meta', new ItineraryMeta(['itinerary_id' => $this->id]));
            }
            $this->meta->fill($metaAttrs);
        }

        return parent::fill($itinAttrs);
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, $this->metaAttributes)) {
            if (!$this->meta) {
                $this->setRelation('meta', new ItineraryMeta(['itinerary_id' => $this->id]));
            }
            $this->meta->setAttribute($key, $value);
            return $this;
        }
        return parent::setAttribute($key, $value);
    }

    public function save(array $options = [])
    {
        $saved = parent::save($options);

        if ($this->meta && $this->meta->isDirty()) {
            $this->meta->itinerary_id = $this->id;
            $this->meta->save();
        }

        return $saved;
    }

    // ─── Delegated Relationships ─────────────────────────────────────

    public function creator()
    {
        return $this->hasOneThrough(
            User::class,
            ItineraryMeta::class,
            'itinerary_id',
            'id',
            'id',
            'creator_id'
        );
    }

    public function owner()
    {
        return $this->hasOneThrough(
            User::class,
            ItineraryMeta::class,
            'itinerary_id',
            'id',
            'id',
            'user_id'
        );
    }

    public function parentItinerary()
    {
        return $this->hasOneThrough(
            Itinerary::class,
            ItineraryMeta::class,
            'itinerary_id',
            'id',
            'id',
            'parent_itinerary_id'
        );
    }

    public function draftItinerary()
    {
        return $this->hasOneThrough(
            Itinerary::class,
            ItineraryMeta::class,
            'itinerary_id',
            'id',
            'id',
            'draft_itinerary_id'
        );
    }

    // ─── Scopes ──────────────────────────────────────────────────────

    public function scopeOriginal($query)
    {
        return $query->whereDoesntHave('meta');
    }

    public function scopeCreatorCopies($query)
    {
        return $query->whereHas('meta', fn($q) => $q->whereNotNull('creator_id'));
    }

    public function scopeApproved($query)
    {
        return $query->whereHas('meta', fn($q) => $q->where('status', 'approved'));
    }

    public function scopeUserCopies($query, $userId)
    {
        return $query->whereHas('meta', fn($q) => $q->where('user_id', $userId));
    }

    public function scopePendingApproval($query)
    {
        return $query->whereHas('meta', fn($q) => $q->where('status', 'pending'));
    }

    public function scopeDraft($query)
    {
        return $query->whereHas('meta', fn($q) => $q->where('status', 'draft'));
    }

    public function scopeEditPending($query)
    {
        return $query->whereHas('meta', fn($q) => $q->where('status', 'edit_pending'));
    }

    public function scopeRemovalRequested($query)
    {
        return $query->whereHas('meta', fn($q) => $q->where('removal_status', 'requested'));
    }

    // ─── Content Relationships ───────────────────────────────────────

    public function locations()
    {
        return $this->hasMany(ItineraryLocation::class);
    }

    public function schedules()
    {
        return $this->hasMany(ItinerarySchedule::class);
    }

    public function basePricing()
    {
        return $this->hasOne(ItineraryBasePricing::class, 'itinerary_id');
    }

    public function inclusionsExclusions()
    {
        return $this->hasMany(ItineraryInclusionExclusion::class);
    }

    public function mediaGallery()
    {
        return $this->hasMany(ItineraryMediaGallery::class);
    }

    public function seo()
    {
        return $this->hasOne(ItinerarySeo::class);
    }

    public function categories()
    {
        return $this->hasMany(ItineraryCategory::class);
    }

    public function attributes()
    {
        return $this->hasMany(ItineraryAttribute::class);
    }

    public function tags()
    {
        return $this->hasMany(ItineraryTag::class);
    }

    public function availability()
    {
        return $this->hasOne(ItineraryAvailability::class);
    }

    public function orders()
    {
        return $this->morphMany(Order::class, 'orderable');
    }

    public function reviews()
    {
        return $this->morphMany(Review::class, 'item', 'item_type', 'item_id');
    }

    public function getItemTypeAttribute($value)
    {
        return $value ?? strtolower(class_basename($this));
    }

    public function addons()
    {
        return $this->hasMany(ItineraryAddon::class);
    }

    public function postTags()
    {
        return $this->morphMany(PostItemTag::class, 'taggable');
    }

    public function copies()
    {
        return $this->hasManyThrough(
            Itinerary::class,
            ItineraryMeta::class,
            'itinerary_id',
            'id',
            'id',
            'parent_itinerary_id'
        );
    }

    public function likes()
    {
        return $this->hasMany(ItineraryLike::class);
    }

    public function isLikedBy($userId): bool
    {
        return $this->likes()->where('user_id', $userId)->exists();
    }

}
