<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageLocation extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'city_id',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function city() {
        
        return $this->belongsTo(City::class, 'city_id');
    }
}
