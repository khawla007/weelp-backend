<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageAddon extends Model
{
    protected $table = 'package_addons';

    protected $fillable = [
        'package_id',
        'addon_id',
    ];

    // Relations
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }
}
