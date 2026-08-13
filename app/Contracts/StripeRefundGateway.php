<?php

declare(strict_types=1);

namespace App\Contracts;

interface StripeRefundGateway
{
    public function refundedAmount(string $paymentIntentId): int;

    public function refund(
        string $paymentIntentId,
        int $amountInMinorUnits,
        string $idempotencyKey,
        array $metadata,
    ): object;
}
