<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\StripePaymentIntentGateway;
use RuntimeException;
use Stripe\PaymentIntent;
use Stripe\Stripe;

final class StripePaymentIntentService implements StripePaymentIntentGateway
{
    public function create(int $amount, string $currency, array $metadata): PaymentIntent
    {
        Stripe::setApiKey($this->testSecret());

        return PaymentIntent::create([
            'amount' => $amount,
            'currency' => $currency,
            'automatic_payment_methods' => ['enabled' => true],
            'metadata' => $metadata,
        ]);
    }

    public function retrieve(string $paymentIntentId): PaymentIntent
    {
        Stripe::setApiKey($this->testSecret());

        return PaymentIntent::retrieve($paymentIntentId);
    }

    private function testSecret(): string
    {
        $secret = (string) config('services.stripe.secret');
        if (! str_starts_with($secret, 'sk_test_')) {
            throw new RuntimeException('Stripe test mode is required for checkout operations.');
        }

        return $secret;
    }
}
