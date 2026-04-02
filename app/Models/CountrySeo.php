<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $country_id
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property string|null $keywords
 * @property string|null $og_image_url
 * @property string|null $canonical_url
 * @property string|null $schema_type
 * @property string|null $schema_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Country $country
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo whereCanonicalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo whereCountryId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo whereKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo whereOgImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo whereSchemaData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo whereSchemaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|CountrySeo whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class CountrySeo extends Model
{
    use HasFactory;

    protected $table = 'country_seo';

    protected $fillable = [
        'country_id',
        'meta_title',
        'meta_description',
        'keywords',
        'og_image_url',
        'canonical_url',
        'schema_type',
        'schema_data',
    ];

    // protected $casts = [
    //     'schema_data' => 'array' // Automatically convert JSON string to array
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

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
