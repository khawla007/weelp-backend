<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferAddon extends Model
{
    protected $table = 'transfer_addons';

    protected $fillable = [
        'transfer_id',
        'addon_id',
    ];

    // Relations
    public function transfer()
    {
        return $this->belongsTo(Transfer::class, 'transfer_id');
    }

    public function addon()
    {
        return $this->belongsTo(Addon::class, 'addon_id');
    }
}
