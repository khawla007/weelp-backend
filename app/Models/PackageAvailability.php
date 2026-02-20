<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageAvailability extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'date_based_package',
        'start_date',
        'end_date',
        'quantity_based_package',
        'max_quantity',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
