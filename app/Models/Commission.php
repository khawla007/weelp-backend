<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
 * @mixin \Illuminate\Database\Eloquent\Model
 */
class Commission extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        // Stamp paid_at the first time status flips to 'paid' so the Payouts view
        // has an accurate event timestamp without a separate write path.
        static::saving(function (Commission $commission) {
            if ($commission->isDirty('status') && $commission->status === 'paid' && $commission->paid_at === null) {
                $commission->paid_at = now();
            }
        });
    }

    protected $fillable = [
        'creator_id', 'order_id', 'commission_rate', 'commission_amount', 'status', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'paid_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
