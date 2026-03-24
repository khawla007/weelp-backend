<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'item_type',
        'item_id',
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

    // Polymorphic relation
    public function item()
    {
        return $this->morphTo(__FUNCTION__, 'item_type', 'item_id');
    }

    public function mediaGallery()
    {
        return $this->hasMany(ReviewMediaGallery::class)->orderBy('sort_order');
    }
}
