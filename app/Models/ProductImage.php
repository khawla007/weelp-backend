<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Product|null $product
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductImage query()
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'variant_id', 'image_url'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
