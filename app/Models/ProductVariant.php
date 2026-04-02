<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property-read \App\Models\ProductPrice|null $prices
 * @property-read \App\Models\Product|null $product
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ProductVariant query()
 * @mixin \Eloquent
 */
class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'variant_name', 'sku'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function prices()
    {
        return $this->hasOne(ProductPrice::class, 'variant_id');
    }
}
