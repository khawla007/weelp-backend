<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Package|null $package
 * @property-read \App\Models\Transfer|null $transfer
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransferMapping newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransferMapping newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|PackageTransferMapping query()
 *
 * @mixin \Eloquent
 */
class PackageTransferMapping extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_id',
        'transfer_id',
    ];

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class);
    }
}
