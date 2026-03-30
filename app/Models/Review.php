<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'order_id',
        'item_type',
        'item_id',
        'item_name_snapshot',
        'item_slug_snapshot',
        'rating',
        'review_text',
        'status',
        'is_featured',
    ];

    protected $casts = [
        'is_featured' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Polymorphic relation
    public function item()
    {
        return $this->morphTo(__FUNCTION__, 'item_type', 'item_id');
    }

    public function mediaGallery()
    {
        return $this->hasMany(ReviewMediaGallery::class)->orderBy('sort_order');
    }

    /**
     * Get display name - uses snapshot if item deleted
     */
    public function getDisplayName(): string
    {
        return $this->item?->name ?? $this->item_name_snapshot ?? 'Archived Item';
    }

    /**
     * Get display slug - uses snapshot if item deleted
     */
    public function getDisplaySlug(): ?string
    {
        return $this->item?->slug ?? $this->item_slug_snapshot;
    }

    /**
     * Check if item still exists in database
     */
    public function hasLiveItem(): bool
    {
        return $this->item !== null;
    }
}
