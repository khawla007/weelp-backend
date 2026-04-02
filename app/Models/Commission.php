<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $creator_id
 * @property int $order_id
 * @property numeric $commission_rate
 * @property numeric $commission_amount
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $creator
 * @property-read \App\Models\Order $order
 *
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereCommissionAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereCommissionRate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereCreatorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Commission whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class Commission extends Model
{
    use HasFactory;

    protected $fillable = [
        'creator_id', 'order_id', 'commission_rate', 'commission_amount', 'status',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
        ];
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
