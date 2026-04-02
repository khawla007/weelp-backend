<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property string $status
 * @property bool $is_featured
 * @property string $taxonomy
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Blog> $blogs
 * @property-read int|null $blogs_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Itinerary> $itineraries
 * @property-read int|null $itineraries_count
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereIsFeatured($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereTaxonomy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Tag whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Tag extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'taxonomy', 'post_type', 'status', 'is_featured'];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    // Automatically generate slug when creating or updating
    protected static function boot()
    {
        parent::boot();

        // static::saving(function ($tag) {
        //     $tag->slug = Str::slug(str_replace(' ', '_', strtolower($tag->name)), '_');
        // });
    }

    public function itineraries(): BelongsToMany
    {
        return $this->belongsToMany(Itinerary::class, 'itinerary_tags');
    }

    // public function blogs()
    // {
    //     return $this->hasMany(Blog::class);
    // }
    public function blogs(): BelongsToMany
    {
        return $this->belongsToMany(Blog::class, 'blog_tag');
    }
}
