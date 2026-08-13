<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CancellationRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REFUND_PROCESSING = 'refund_processing';

    public const STATUS_REFUND_FAILED = 'refund_failed';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const ADMIN_ATTENTION_STATUSES = [
        self::STATUS_PENDING,
        self::STATUS_REFUND_PROCESSING,
        self::STATUS_REFUND_FAILED,
    ];

    protected $fillable = [
        'order_id',
        'customer_id',
        'status',
        'reason',
        'requested_at',
        'policy_version',
        'policy_snapshot',
        'travel_starts_at',
        'seconds_remaining',
        'paid_amount',
        'currency',
        'suggested_deduction_percentage',
        'suggested_deduction_amount',
        'suggested_refund_amount',
        'final_refund_amount',
        'final_deduction_amount',
        'decision_explanation',
        'decided_by',
        'decided_at',
        'stripe_refund_id',
        'idempotency_key',
        'failure_code',
        'failure_summary',
        'failure_disposition',
        'refund_outcome',
        'refund_completed_at',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'policy_snapshot' => 'array',
        'travel_starts_at' => 'datetime',
        'seconds_remaining' => 'integer',
        'paid_amount' => 'decimal:2',
        'suggested_deduction_percentage' => 'decimal:2',
        'suggested_deduction_amount' => 'decimal:2',
        'suggested_refund_amount' => 'decimal:2',
        'final_refund_amount' => 'decimal:2',
        'final_deduction_amount' => 'decimal:2',
        'decided_at' => 'datetime',
        'refund_completed_at' => 'datetime',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function decidingAdmin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function getDeductionPercentageAttribute(): int
    {
        return (int) $this->suggested_deduction_percentage;
    }

    public function getSuggestedDeductionAttribute(): ?string
    {
        return $this->suggested_deduction_amount;
    }

    public function getSuggestedRefundAttribute(): ?string
    {
        return $this->suggested_refund_amount;
    }

    public function getFinalRefundAttribute(): ?string
    {
        return $this->final_refund_amount;
    }

    public function getFinalDeductionAttribute(): ?string
    {
        return $this->final_deduction_amount;
    }

    public function canRetry(): bool
    {
        if ($this->status === self::STATUS_REFUND_FAILED) {
            return true;
        }

        if ($this->status !== self::STATUS_REFUND_PROCESSING || ! $this->updated_at) {
            return false;
        }

        $staleAfter = (int) config('cancellation.refund_processing_stale_after_seconds', 300);

        return $this->updated_at->copy()->addSeconds($staleAfter)->lessThanOrEqualTo(now());
    }

    public function canReject(): bool
    {
        return $this->status === self::STATUS_PENDING
            || ($this->status === self::STATUS_REFUND_FAILED
                && $this->failure_disposition === 'definitive');
    }

    public function needsAdminAttention(): bool
    {
        return in_array($this->status, self::ADMIN_ATTENTION_STATUSES, true);
    }
}
