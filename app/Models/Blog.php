<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Blog extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'content', 'publish', 'excerpt',
    ];

    protected $casts = [
        'publish' => 'boolean',
    ];

    public function media()
    {
        return $this->belongsToMany(Media::class, 'blog_media');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'blog_category');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'blog_tag');
    }
}
