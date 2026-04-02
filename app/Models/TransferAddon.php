<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $transfer_id
 * @property int $addon_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Addon $addon
 * @property-read \App\Models\Transfer $transfer
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferAddon newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferAddon newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferAddon query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferAddon whereAddonId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferAddon whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferAddon whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferAddon whereTransferId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|TransferAddon whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class TransferAddon extends Model
{
    protected $table = 'transfer_addons';

    protected $fillable = [
        'transfer_id',
        'addon_id',
    ];

    // Relations
    public function transfer(): BelongsTo
    {
        return $this->belongsTo(Transfer::class, 'transfer_id');
    }

    public function addon(): BelongsTo
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }
}
