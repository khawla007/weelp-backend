<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityAddon extends Model
{
    protected $table = 'activity_addons';

    protected $fillable = [
        'activity_id',
        'addon_id',
    ];

    // Relations
    public function activity()
    {
        return $this->belongsTo(Activity::class, 'activity_id');
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }
}
