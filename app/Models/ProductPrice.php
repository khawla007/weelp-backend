<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\ProductVariant|null $variant
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductPrice query()
 *
 * @mixin \Eloquent
 */
class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'variant_id', 'price', 'sale_price'];

    public function product(): BelongsTo
    {
        // return $this->belongsTo(Product::class);
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        // return $this->belongsTo(ProductVariant::class);
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
