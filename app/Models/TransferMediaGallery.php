<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferMediaGallery extends Model
{
    use HasFactory;

    protected $table = 'transfer_media_gallery';

    protected $fillable = [
        'transfer_id',
        'media_id',
    ];

    // Relationship with Transfer
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }

    public function media()
    {
        return $this->belongsTo(Media::class, 'media_id');
    }
}