<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductPrice extends Model
{
    use HasFactory;

    protected $fillable = ['product_id', 'variant_id', 'price', 'sale_price'];

    public function product()
    {
        // return $this->belongsTo(Product::class);
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        // return $this->belongsTo(ProductVariant::class);
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }
}
