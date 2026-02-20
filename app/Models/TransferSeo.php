<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransferSeo extends Model
{
    use HasFactory;

    protected $table = 'transfer_seo';
    protected $fillable = [
        'transfer_id', 'meta_title', 'meta_description', 
        'keywords', 'og_image_url', 'canonical_url', 
        'schema_type', 'schema_data'
    ];

    protected $casts = [
        'schema_data' => 'array'
    ];

    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
