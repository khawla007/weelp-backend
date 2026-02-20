<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderEmergencyContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'contact_name', 'contact_phone', 'relationship'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

