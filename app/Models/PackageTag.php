<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageTag extends Model
{
    protected $fillable = [
        'package_id', 'tag_id'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}
