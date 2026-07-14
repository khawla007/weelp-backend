<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Package;
use App\Models\Transfer;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class CheckoutQuoteService
{
    public function __construct(
        private readonly ActivityDiscountService $activityDiscounts,
        private readonly PackagePricingService $packagePricing,
    ) {}

    /**
     * @return array{amount: float, currency: string, base_amount: float, addons: array<int, array{addon_id: int, addon_name: string, price: float}>, addons_amount: float, variation_id: int|null}
     */
    public function quote(array $selection): array
    {
        $orderable = $this->resolveOrderable($selection);
        $travelDate = $this->resolveTravelDate($selection);
        $adults = (int) ($selection['number_of_adults'] ?? 0);
        $children = (int) ($selection['number_of_children'] ?? 0);

        if ($adults < 0 || $children < 0 || $adults + $children < 1) {
            throw new DomainException('invalid_guests');
        }

        $this->assertAvailable($orderable, $travelDate, $adults + $children);

        [$baseAmount, $currency, $variationId, $selectionExtras] = $this->baseQuote(
            $orderable,
            $travelDate,
            $adults,
            $children,
            isset($selection['variation_id']) ? (int) $selection['variation_id'] : null,
            (int) ($selection['bag_count'] ?? 0),
            (int) ($selection['waiting_minutes'] ?? 0),
        );
        $addons = $this->resolveAddons($orderable, $selection['addon_ids'] ?? []);
        $addonsAmount = round($selectionExtras + array_sum(array_column($addons, 'price')), 2);
        $amount = round($baseAmount + $addonsAmount, 2);

        if ($amount <= 0) {
            throw new DomainException('amount_unresolved');
        }

        return [
            'amount' => $amount,
            'currency' => strtoupper($currency),
            'base_amount' => round($baseAmount, 2),
            'addons' => $addons,
            'addons_amount' => $addonsAmount,
            'variation_id' => $variationId,
        ];
    }

    private function resolveOrderable(array $selection): Model
    {
        $map = [
            'activity' => Activity::class,
            'package' => Package::class,
            'itinerary' => Itinerary::class,
            'transfer' => Transfer::class,
        ];
        $class = $map[$selection['order_type'] ?? ''] ?? null;
        $id = filter_var($selection['orderable_id'] ?? null, FILTER_VALIDATE_INT);
        $orderable = $class && $id ? $class::find($id) : null;

        if (! $orderable) {
            throw new DomainException('item_unavailable');
        }

        return $orderable;
    }

    private function resolveTravelDate(array $selection): CarbonImmutable
    {
        try {
            $date = CarbonImmutable::parse((string) ($selection['travel_date'] ?? ''))->startOfDay();
        } catch (\Throwable) {
            throw new DomainException('travel_date_invalid');
        }

        if ($date->isBefore(CarbonImmutable::today())) {
            throw new DomainException('travel_date_invalid');
        }

        return $date;
    }

    private function assertAvailable(Model $orderable, CarbonImmutable $travelDate, int $headcount): void
    {
        if ($orderable instanceof Transfer) {
            $orderable->loadMissing(['schedule', 'pricingAvailability.availability']);
            $schedule = $orderable->schedule;
            if ($schedule) {
                if ((int) $schedule->maximum_passengers > 0 && $headcount > (int) $schedule->maximum_passengers) {
                    throw new DomainException('item_capacity_exceeded');
                }

                $rawBlackoutDates = $schedule->blackout_dates ?? [];
                $blackoutDates = collect(is_array($rawBlackoutDates) ? $rawBlackoutDates : explode(',', (string) $rawBlackoutDates))->filter()->map(
                    fn ($date) => CarbonImmutable::parse($date)->toDateString(),
                );
                if ($blackoutDates->contains($travelDate->toDateString())) {
                    throw new DomainException('item_date_unavailable');
                }

                if ($schedule->availability_type === 'custom_schedule') {
                    $rawAvailableDays = $schedule->available_days ?? [];
                    $availableDays = collect(is_array($rawAvailableDays) ? $rawAvailableDays : explode(',', (string) $rawAvailableDays))->filter()->map(
                        fn ($day) => strtolower(trim((string) $day)),
                    );
                    if ($availableDays->isNotEmpty() && ! $availableDays->contains(strtolower($travelDate->format('l')))) {
                        throw new DomainException('item_date_unavailable');
                    }
                }
            }

            $slot = $orderable->pricingAvailability?->availability;
            if ($slot) {
                if (CarbonImmutable::parse($slot->date)->toDateString() !== $travelDate->toDateString()) {
                    throw new DomainException('item_date_unavailable');
                }
            }

            return;
        }

        $orderable->loadMissing('availability');
        $availability = $orderable->availability;
        if (! $availability) {
            return;
        }

        $prefix = $orderable instanceof Activity ? 'activity' : ($orderable instanceof Package ? 'package' : 'itinerary');
        $dateBased = (bool) $availability->getAttribute("date_based_{$prefix}");
        $quantityBased = (bool) $availability->getAttribute("quantity_based_{$prefix}");

        if ($dateBased) {
            $start = $availability->start_date ? CarbonImmutable::parse($availability->start_date)->startOfDay() : null;
            $end = $availability->end_date ? CarbonImmutable::parse($availability->end_date)->startOfDay() : null;
            if (($start && $travelDate->lt($start)) || ($end && $travelDate->gt($end))) {
                throw new DomainException('item_date_unavailable');
            }
        }

        if ($quantityBased && (int) $availability->max_quantity > 0 && $headcount > (int) $availability->max_quantity) {
            throw new DomainException('item_capacity_exceeded');
        }
    }

    /** @return array{float, string, int|null, float} */
    private function baseQuote(
        Model $orderable,
        CarbonImmutable $travelDate,
        int $adults,
        int $children,
        ?int $variationId,
        int $bagCount,
        int $waitingMinutes,
    ): array {
        if ($bagCount < 0 || $waitingMinutes < 0) {
            throw new DomainException('transfer_quantities_invalid');
        }

        if ($orderable instanceof Activity) {
            $orderable->loadMissing(['pricing', 'groupDiscounts', 'earlyBirdDiscount', 'lastMinuteDiscount']);
            try {
                $quote = $this->activityDiscounts->quote($orderable, $adults + $children, $travelDate);
            } catch (RuntimeException) {
                throw new DomainException('activity_pricing_missing');
            }

            return [(float) $quote['final_amount'], (string) ($quote['currency'] ?: 'USD'), null, 0.0];
        }

        if ($orderable instanceof Package) {
            $variation = $this->packagePricing->resolveVariationFor($orderable, $variationId);
            $amount = $this->packagePricing->priceFor(
                $orderable,
                $variation->id,
                $travelDate,
                $adults,
                $children,
            );

            return [$amount, (string) ($orderable->basePricing?->currency ?: 'USD'), $variation->id, 0.0];
        }

        if ($orderable instanceof Itinerary) {
            $orderable->loadMissing(
                'basePricing',
                'schedules.activities.activity.pricing',
                'schedules.transfers.transfer.route',
                'schedules.transfers.transfer.pricingAvailability',
            );
            if ($orderable->max_guests !== null && $adults + $children > $orderable->max_guests) {
                throw new DomainException('guests_exceed_transfer_capacity');
            }

            return [
                $orderable->priceForGuests($adults, $children),
                (string) ($orderable->basePricing?->currency ?: $orderable->schedule_total_currency ?: 'USD'),
                null,
                0.0,
            ];
        }

        /** @var Transfer $orderable */
        $orderable->loadMissing(['route', 'pricingAvailability']);
        $base = $orderable->computeRoutePrice($adults + $children);
        $extras = ($orderable->luggagePerBagRate() * $bagCount)
            + ($orderable->waitingPerMinuteRate() * $waitingMinutes);

        return [
            $base,
            (string) ($orderable->routeCurrency() ?: 'USD'),
            null,
            round($extras, 2),
        ];
    }

    /**
     * @return array<int, array{addon_id: int, addon_name: string, price: float}>
     */
    private function resolveAddons(Model $orderable, mixed $submittedIds): array
    {
        if (! is_array($submittedIds)) {
            throw new DomainException('addon_invalid');
        }

        $ids = array_values(array_unique(array_map('intval', $submittedIds)));
        if ($ids === []) {
            return [];
        }

        $rows = $orderable->addons()->with('addon')->whereIn('addon_id', $ids)->get();
        if ($rows->count() !== count($ids)) {
            throw new DomainException('addon_invalid');
        }

        return $rows->map(function ($row): array {
            $addon = $row->addon;
            if (! $addon || ! $addon->active_status) {
                throw new DomainException('addon_invalid');
            }

            return [
                'addon_id' => (int) $addon->id,
                'addon_name' => (string) $addon->name,
                'price' => (float) ($addon->sale_price !== null ? $addon->sale_price : $addon->price),
            ];
        })->values()->all();
    }
}
