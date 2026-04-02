<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $transfer_id
 * @property string $meta_title
 * @property string|null $meta_description
 * @property string|null $keywords
 * @property string|null $og_image_url
 * @property string|null $canonical_url
 * @property string|null $schema_type
 * @property array<array-key, mixed>|null $schema_data
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Transfer $transfer
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo whereCanonicalUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo whereKeywords($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo whereMetaDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo whereMetaTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo whereOgImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo whereSchemaData($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo whereSchemaType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo whereTransferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferSeo whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class TransferSeo extends Model
{
    use HasFactory;

    protected $table = 'transfer_seo';

    protected $fillable = [
        'transfer_id', 'meta_title', 'meta_description',
        'keywords', 'og_image_url', 'canonical_url',
        'schema_type', 'schema_data',
    ];

    protected $casts = [
        'schema_data' => 'array',
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
