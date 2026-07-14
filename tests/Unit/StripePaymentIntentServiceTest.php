<?php

namespace Tests\Unit;

use App\Services\StripePaymentIntentService;
use RuntimeException;
use Tests\TestCase;

class StripePaymentIntentServiceTest extends TestCase
{
    public function test_create_refuses_non_test_stripe_secret_before_sdk_call(): void
    {
        config(['services.stripe.secret' => 'sk_live_redacted']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stripe test mode is required');

        app(StripePaymentIntentService::class)->create(100, 'usd', []);
    }

    public function test_retrieve_refuses_non_test_stripe_secret_before_sdk_call(): void
    {
        config(['services.stripe.secret' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Stripe test mode is required');

        app(StripePaymentIntentService::class)->retrieve('pi_redacted');
    }
}
