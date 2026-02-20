<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PackageAttribute extends Model
{
    protected $fillable = [
        'package_id', 'attribute_id', 'attribute_value'
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function attribute() {
        return $this->belongsTo(Attribute::class, 'attribute_id');
    }
}
