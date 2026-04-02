<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $state_id
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $keywords
 * @property string|null $og_image_url
 * @property string|null $canonical_url
 * @property string|null $schema_type
 * @property string|null $schema_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\State $state
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo whereCanonicalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo whereKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo whereOgImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo whereSchemaData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo whereSchemaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo whereStateId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StateSeo whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class StateSeo extends Model
{
    use HasFactory;

    protected $table = 'state_seo';

    protected $fillable = [
        'state_id',
        'meta_title',
        'meta_description',
        'keywords',
        'og_image_url',
        'canonical_url',
        'schema_type',
        'schema_data',
    ];

    // protected $casts = [
    //     'schema_data' => 'array',
    // ];

    // Store JSON as raw JSON (Not as escaped string)
    public function setSchemaDataAttribute($value)
    {
        $this->attributes['schema_data'] = json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    // Retrieve JSON as array
    public function getSchemaDataAttribute($value)
    {
        return json_decode($value, true);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
