<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityTag extends Model
{
    protected $table = 'activity_tag';
    protected $fillable = [
        'activity_id', 'tag_id'
    ];

    public function activity()
    {
        return $this->belongsTo(Activity::class);
    }

    public function tag()
    {
        return $this->belongsTo(Tag::class, 'tag_id');
    }
}
