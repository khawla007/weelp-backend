<?php

declare(strict_types=1);

namespace App\Contracts;

interface StripePaymentIntentGateway
{
    public function create(int $amount, string $currency, array $metadata): object;

    public function retrieve(string $paymentIntentId): object;
}
