<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageInformation extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'section_title',
        'content',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
