<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Package;
use App\Models\PackagePriceVariation;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DomainException;

final class PackagePricingService
{
    /**
     * Server-authoritative price for a Package booking.
     *
     * Schema: Package hasOne PackageBasePricing hasMany PackagePriceVariation.
     * Each variation is a fixed-price tier (e.g. Solo / Couple / Family) capped
     * by max_guests. Pricing is flat per tier — no pax multiplier.
     *
     * Infants are accepted for signature parity with other order types but are
     * not counted toward capacity (matches Itinerary convention).
     *
     * @throws DomainException 422-mappable: missing pricing, invalid variation,
     *                         date out of window, blackout, capacity overflow.
     */
    public function priceFor(
        Package $package,
        ?int $variationId,
        DateTimeInterface $date,
        int $adults,
        int $children,
        int $infants = 0,
    ): float {
        $package->loadMissing('basePricing.variations', 'basePricing.blackoutDates');

        $base = $package->basePricing;
        if (! $base || $base->variations->isEmpty()) {
            throw new DomainException('package_pricing_missing');
        }

        $variation = $this->resolveVariation($base->variations, $variationId);
        $travel = CarbonImmutable::instance($date)->startOfDay();

        $this->assertInWindow($base->start_date, $base->end_date, $travel);
        $this->assertNotBlackout($base->blackoutDates, $travel);
        $this->assertCapacity($variation, $adults + $children);

        return round(self::effectivePrice($variation), 2);
    }

    /**
     * Resolve which variation will be charged for a Package booking.
     * Used by callers that need to persist the booked variation (e.g. Order.variation_id)
     * without re-implementing the fallback rule.
     *
     * @throws DomainException
     */
    public function resolveVariationFor(Package $package, ?int $variationId): PackagePriceVariation
    {
        $package->loadMissing('basePricing.variations');
        $base = $package->basePricing;
        if (! $base || $base->variations->isEmpty()) {
            throw new DomainException('package_pricing_missing');
        }

        return $this->resolveVariation($base->variations, $variationId);
    }

    /**
     * Charge sale_price when it is a real discount; else regular_price.
     * sale_price is non-nullable in schema, so 0 is treated as "unset".
     */
    private static function effectivePrice(PackagePriceVariation $variation): float
    {
        $regular = (float) $variation->regular_price;
        $sale = (float) $variation->sale_price;

        return ($sale > 0 && $sale < $regular) ? $sale : $regular;
    }

    private function resolveVariation($variations, ?int $variationId): PackagePriceVariation
    {
        if ($variationId === null) {
            return $variations->first();
        }

        $match = $variations->firstWhere('id', $variationId);
        if (! $match) {
            throw new DomainException('package_variation_invalid');
        }

        return $match;
    }

    private function assertInWindow(?string $start, ?string $end, CarbonImmutable $travel): void
    {
        if ($start === null && $end === null) {
            return;
        }
        $startDate = $start ? CarbonImmutable::parse($start)->startOfDay() : null;
        $endDate = $end ? CarbonImmutable::parse($end)->startOfDay() : null;

        if (($startDate && $travel->lt($startDate)) || ($endDate && $travel->gt($endDate))) {
            throw new DomainException('package_date_out_of_window');
        }
    }

    private function assertNotBlackout($blackoutDates, CarbonImmutable $travel): void
    {
        $hit = $blackoutDates->first(
            fn ($row) => CarbonImmutable::parse($row->date)->startOfDay()->equalTo($travel)
        );
        if ($hit) {
            throw new DomainException('package_date_blackout');
        }
    }

    private function assertCapacity(PackagePriceVariation $variation, int $headcount): void
    {
        if ($headcount < 1) {
            throw new DomainException('package_invalid_headcount');
        }
        if ((int) $variation->max_guests > 0 && $headcount > (int) $variation->max_guests) {
            throw new DomainException('package_capacity_exceeded');
        }
    }
}
