<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $order_id
 * @property string $payment_status
 * @property string|null $stripe_session_id
 * @property string|null $payment_intent_id
 * @property string $payment_method
 * @property numeric|null $amount
 * @property bool $is_custom_amount
 * @property numeric|null $custom_amount
 * @property numeric|null $total_amount
 * @property string|null $currency
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Order $order
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereCurrency($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereCustomAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereIsCustomAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment wherePaymentIntentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereStripeSessionId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|OrderPayment whereUpdatedAt($value)
 *
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class OrderPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id', 'payment_status', 'stripe_session_id', 'payment_intent_id', 'payment_method',
        'amount', 'is_custom_amount', 'custom_amount', 'total_amount', 'currency',
    ];

    protected $casts = [
        'is_custom_amount' => 'boolean',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
