<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\StripeRefundGateway;
use App\Exceptions\RefundGatewayException;
use RuntimeException;
use Stripe\Charge;
use Stripe\Exception\ApiConnectionException;
use Stripe\Exception\CardException;
use Stripe\Exception\InvalidRequestException;
use Stripe\Refund;
use Stripe\Stripe;
use Throwable;

final class StripeRefundService implements StripeRefundGateway
{
    public function refundedAmount(string $paymentIntentId): int
    {
        try {
            Stripe::setApiKey($this->testSecret());
            $charges = Charge::all([
                'payment_intent' => $paymentIntentId,
                'limit' => 100,
            ]);

            return array_reduce(
                $charges->data,
                static fn (int $total, Charge $charge): int => $total + max(0, (int) $charge->amount_refunded),
                0,
            );
        } catch (CardException|InvalidRequestException $exception) {
            throw RefundGatewayException::definitive(
                $exception->getStripeCode() ?? 'invalid_request',
                $exception->getMessage(),
                $exception,
            );
        } catch (ApiConnectionException $exception) {
            throw RefundGatewayException::indeterminate('provider_timeout', $exception->getMessage(), $exception);
        } catch (RefundGatewayException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw RefundGatewayException::indeterminate('provider_error', $exception->getMessage(), $exception);
        }
    }

    public function refund(
        string $paymentIntentId,
        int $amountInMinorUnits,
        string $idempotencyKey,
        array $metadata,
    ): object {
        try {
            Stripe::setApiKey($this->testSecret());

            return Refund::create([
                'payment_intent' => $paymentIntentId,
                'amount' => $amountInMinorUnits,
                'metadata' => $metadata,
            ], [
                'idempotency_key' => $idempotencyKey,
            ]);
        } catch (CardException|InvalidRequestException $exception) {
            throw RefundGatewayException::definitive(
                $exception->getStripeCode() ?? 'invalid_request',
                $exception->getMessage(),
                $exception,
            );
        } catch (ApiConnectionException $exception) {
            throw RefundGatewayException::indeterminate('provider_timeout', $exception->getMessage(), $exception);
        } catch (RefundGatewayException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw RefundGatewayException::indeterminate('provider_error', $exception->getMessage(), $exception);
        }
    }

    private function testSecret(): string
    {
        $secret = (string) config('services.stripe.secret');
        if (! str_starts_with($secret, 'sk_test_')) {
            throw new RuntimeException('Stripe test mode is required for refund operations.');
        }

        return $secret;
    }
}
