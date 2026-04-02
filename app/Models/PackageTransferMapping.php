<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackageTransferMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'transfer_id',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
