<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\Package;
use Carbon\CarbonImmutable;
use Carbon\Exceptions\InvalidFormatException;
use DomainException;

class CancellationPolicyService
{
    private const CURRENCY_EXPONENTS = [
        'BIF' => 0,
        'CLP' => 0,
        'DJF' => 0,
        'GNF' => 0,
        'JPY' => 0,
        'KMF' => 0,
        'KRW' => 0,
        'MGA' => 0,
        'PYG' => 0,
        'RWF' => 0,
        'UGX' => 0,
        'VND' => 0,
        'VUV' => 0,
        'XAF' => 0,
        'XOF' => 0,
        'XPF' => 0,
        'USD' => 2,
    ];

    /**
     * @return array{
     *     policy_version: string,
     *     policy_snapshot: array<string, mixed>,
     *     requested_at: string,
     *     travel_starts_at: string,
     *     seconds_remaining: int,
     *     paid_amount: string,
     *     currency: string,
     *     deduction_percentage: int,
     *     suggested_deduction: string,
     *     suggested_refund: string
     * }
     */
    public function quote(Order $order, ?CarbonImmutable $requestedAt = null): array
    {
        $timezone = (string) config('app.timezone');
        $requestedAt = ($requestedAt ?? CarbonImmutable::now($timezone))->setTimezone($timezone);
        $travelStartsAt = $this->travelStartsAt($order, $timezone);

        $this->assertEligibleOrder($order, $travelStartsAt, $requestedAt);

        $payment = $order->payment;
        if (! $payment instanceof OrderPayment) {
            throw new DomainException('A paid payment is required.');
        }

        $currency = strtoupper(trim((string) $payment->currency));
        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1) {
            throw new DomainException('Payment currency is unavailable.');
        }

        $exponent = $this->currencyExponent($currency);
        $paidMinor = $this->paidMinorAmount($payment, $exponent);
        if ($paidMinor <= 0) {
            throw new DomainException('A paid payment is required.');
        }

        $secondsRemaining = (int) $requestedAt->diffInSeconds($travelStartsAt, false);
        $policy = config('cancellation');
        $deductionPercentage = $this->deductionPercentage($secondsRemaining, $policy['bands'] ?? []);
        $deductionMinor = min(
            $paidMinor,
            max(0, intdiv(($paidMinor * $deductionPercentage) + 50, 100)),
        );
        $refundMinor = min($paidMinor, max(0, $paidMinor - $deductionMinor));

        return [
            'policy_version' => (string) ($policy['version'] ?? ''),
            'policy_snapshot' => $policy,
            'requested_at' => $requestedAt->toIso8601String(),
            'travel_starts_at' => $travelStartsAt->toIso8601String(),
            'seconds_remaining' => $secondsRemaining,
            'paid_amount' => $this->formatForSnapshot($paidMinor, $exponent),
            'currency' => $currency,
            'deduction_percentage' => $deductionPercentage,
            'suggested_deduction' => $this->formatForSnapshot($deductionMinor, $exponent),
            'suggested_refund' => $this->formatForSnapshot($refundMinor, $exponent),
        ];
    }

    private function assertEligibleOrder(
        Order $order,
        CarbonImmutable $travelStartsAt,
        CarbonImmutable $requestedAt,
    ): void {
        if ($order->trashed() || in_array($order->status, ['completed', 'cancelled', 'refunded'], true)) {
            throw new DomainException('Order is no longer eligible for cancellation.');
        }

        if ($travelStartsAt->lessThanOrEqualTo($requestedAt)) {
            throw new DomainException('Travel has already started.');
        }

        $allowedTypes = [
            (new Activity)->getMorphClass(),
            Activity::class,
            (new Package)->getMorphClass(),
            Package::class,
            (new Itinerary)->getMorphClass(),
            Itinerary::class,
        ];

        if (! in_array($order->orderable_type, $allowedTypes, true)) {
            throw new DomainException('This booking type cannot be cancelled online.');
        }

        $order->loadMissing('payment');
        if (! $order->payment instanceof OrderPayment
            || ! in_array($order->payment->payment_status, ['paid', 'partially_refunded'], true)) {
            throw new DomainException('A paid payment is required.');
        }

        if (blank($order->payment->payment_intent_id)) {
            throw new DomainException('A refundable payment reference is required.');
        }
    }

    private function travelStartsAt(Order $order, string $timezone): CarbonImmutable
    {
        $time = trim((string) $order->preferred_time);
        if (preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $time) !== 1) {
            throw new DomainException('Travel timing is unavailable.');
        }

        if (strlen($time) === 5) {
            $time .= ':00';
        }
        $dateTime = trim((string) $order->travel_date).' '.trim((string) $time);

        try {
            $travelStartsAt = CarbonImmutable::createFromFormat('!Y-m-d H:i:s', $dateTime, $timezone);
        } catch (InvalidFormatException) {
            throw new DomainException('Travel timing is unavailable.');
        }

        if ($travelStartsAt === false || $travelStartsAt->format('Y-m-d H:i:s') !== $dateTime) {
            throw new DomainException('Travel timing is unavailable.');
        }

        return $travelStartsAt;
    }

    /** @param array<int, array<string, int>> $bands */
    private function deductionPercentage(int $secondsRemaining, array $bands): int
    {
        foreach ($bands as $band) {
            if ($secondsRemaining >= ($band['minimum_seconds'] ?? PHP_INT_MAX)) {
                return (int) ($band['deduction_percentage'] ?? 100);
            }
        }

        return 100;
    }

    private function paidMinorAmount(OrderPayment $payment, int $exponent): int
    {
        $amount = $payment->is_custom_amount
            ? $payment->custom_amount
            : ($payment->total_amount ?? $payment->amount);

        if ($amount === null) {
            return 0;
        }

        return $this->parseMinorUnits((string) $amount, $exponent);
    }

    private function currencyExponent(string $currency): int
    {
        return self::CURRENCY_EXPONENTS[$currency] ?? 2;
    }

    private function parseMinorUnits(string $amount, int $exponent): int
    {
        if (! preg_match('/^(\d+)(?:\.(\d+))?$/', trim($amount), $matches)) {
            throw new DomainException('Payment amount is unavailable.');
        }

        $whole = (int) $matches[1];
        $fraction = $matches[2] ?? '';
        $factor = 10 ** $exponent;
        $minor = ($whole * $factor) + (int) str_pad(substr($fraction, 0, $exponent), $exponent, '0');

        $roundingDigit = $fraction[$exponent] ?? null;
        if ($roundingDigit !== null && (int) $roundingDigit >= 5) {
            $minor++;
        }

        return $minor;
    }

    private function formatForSnapshot(int $minor, int $exponent): string
    {
        if ($exponent === 0) {
            return $minor.'.00';
        }

        $factor = 10 ** $exponent;

        return intdiv($minor, $factor).'.'.str_pad((string) ($minor % $factor), 2, '0', STR_PAD_LEFT);
    }
}
