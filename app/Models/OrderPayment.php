<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'payment_status', 'stripe_session_id', 'payment_intent_id', 'payment_method',
        'amount', 'is_custom_amount', 'custom_amount', 'total_amount', 'currency'
    ];

    protected $casts = [
        'is_custom_amount' => 'boolean'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
