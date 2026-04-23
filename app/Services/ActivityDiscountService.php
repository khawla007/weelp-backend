<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityGroupDiscount;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;
use RuntimeException;

final class ActivityDiscountService
{
    public function quote(Activity $activity, int $headcount, ?DateTimeInterface $travelDate = null): array
    {
        $pricing = $activity->pricing;
        if (!$pricing || $pricing->regular_price === null) {
            throw new RuntimeException('Activity pricing is missing');
        }

        $perPax = (float) $pricing->regular_price;
        $currency = $pricing->currency ?? null;
        $daysAhead = $this->computeDaysAhead($travelDate);

        if ($headcount === 0) {
            return $this->emptyQuote(0, $perPax, 0.0, $currency, $daysAhead);
        }

        $subtotal = round($headcount * $perPax, 2);

        $group = $this->computeGroup($activity, $headcount, $perPax);
        $eb = $this->computeEarlyBird($activity, $subtotal, $daysAhead);
        $lm = $this->computeLastMinute($activity, $subtotal, $daysAhead);

        $combined = round($group['discount_total'] + $eb['discount'] + $lm['discount'], 2);
        $finalAmount = max(0.0, round($subtotal - $combined, 2));

        return [
            'headcount' => $headcount,
            'per_pax' => $perPax,
            'subtotal' => $subtotal,
            'selected_tier' => $group['tier'],
            'complete_groups' => $group['complete_groups'],
            'discount_total' => $group['discount_total'],
            'early_bird_discount' => $eb['discount'],
            'last_minute_discount' => $lm['discount'],
            'combined_discount' => $combined,
            'selected_early_bird' => $eb['row'],
            'selected_last_minute' => $lm['row'],
            'days_ahead' => $daysAhead,
            'final_amount' => $finalAmount,
            'currency' => $currency,
        ];
    }

    private function emptyQuote(int $headcount, float $perPax, float $subtotal, ?string $currency, ?int $daysAhead): array
    {
        return [
            'headcount' => $headcount,
            'per_pax' => $perPax,
            'subtotal' => $subtotal,
            'selected_tier' => null,
            'complete_groups' => 0,
            'discount_total' => 0.0,
            'early_bird_discount' => 0.0,
            'last_minute_discount' => 0.0,
            'combined_discount' => 0.0,
            'selected_early_bird' => null,
            'selected_last_minute' => null,
            'days_ahead' => $daysAhead,
            'final_amount' => $subtotal,
            'currency' => $currency,
        ];
    }

    private function computeGroup(Activity $activity, int $headcount, float $perPax): array
    {
        $tiers = $this->loadTiersSorted($activity);
        $triggered = $this->triggeredTiers($tiers, $headcount, $perPax);
        if ($triggered === []) {
            return ['tier' => null, 'complete_groups' => 0, 'discount_total' => 0.0];
        }
        $best = $this->pickBest($triggered);
        return ['tier' => $best['tier'], 'complete_groups' => $best['complete_groups'], 'discount_total' => (float) $best['discount_total']];
    }

    private function computeDaysAhead(?DateTimeInterface $travelDate): ?int
    {
        if ($travelDate === null) {
            return null;
        }
        $today = CarbonImmutable::today();
        $travel = CarbonImmutable::instance($travelDate)->startOfDay();
        return (int) floor($today->diffInDays($travel, false));
    }

    private function computeEarlyBird(Activity $activity, float $subtotal, ?int $daysAhead): array
    {
        $row = $activity->relationLoaded('earlyBirdDiscount') ? $activity->earlyBirdDiscount : null;
        if (!$row || !$row->enabled || $daysAhead === null || $daysAhead < (int) $row->days_before_start) {
            return ['discount' => 0.0, 'row' => null];
        }
        return ['discount' => $this->applyTimeDiscount($subtotal, $row), 'row' => $row];
    }

    private function computeLastMinute(Activity $activity, float $subtotal, ?int $daysAhead): array
    {
        $row = $activity->relationLoaded('lastMinuteDiscount') ? $activity->lastMinuteDiscount : null;
        if (!$row || !$row->enabled || $daysAhead === null || $daysAhead < 0 || $daysAhead > (int) $row->days_before_start) {
            return ['discount' => 0.0, 'row' => null];
        }
        return ['discount' => $this->applyTimeDiscount($subtotal, $row), 'row' => $row];
    }

    private function applyTimeDiscount(float $subtotal, Model $row): float
    {
        $amount = (float) $row->discount_amount;
        if ($row->discount_type === 'percentage') {
            $pct = min($amount, 100.0);
            return round($subtotal * ($pct / 100), 2);
        }
        return round($amount, 2);
    }

    private function loadTiersSorted(Activity $activity)
    {
        if ($activity->relationLoaded('groupDiscounts')) {
            return $activity->groupDiscounts->sortBy('min_people')->values();
        }

        return $activity->groupDiscounts()->orderBy('min_people', 'asc')->get();
    }

    private function triggeredTiers($tiers, int $headcount, float $perPax): array
    {
        $out = [];
        foreach ($tiers as $tier) {
            if ($headcount < $tier->min_people) {
                continue;
            }

            $isPercentage = $tier->discount_type === 'percentage';
            $completeGroups = (int) floor($headcount / $tier->min_people);
            $discountedPax = $completeGroups * (int) $tier->min_people;

            if ($isPercentage) {
                $amount = min((float) $tier->discount_amount, 100.0);
                $discountTotal = round(($amount / 100) * $perPax * $discountedPax, 2);
            } else {
                $discountTotal = round((float) $tier->discount_amount * $completeGroups, 2);
            }

            $out[] = [
                'tier' => $tier,
                'discount_total' => $discountTotal,
                'complete_groups' => $completeGroups,
            ];
        }

        return $out;
    }

    private function pickBest(array $triggered): array
    {
        usort($triggered, function (array $a, array $b): int {
            if ($a['discount_total'] !== $b['discount_total']) {
                return $b['discount_total'] <=> $a['discount_total'];
            }

            $aPct = $a['tier']->discount_type === 'percentage';
            $bPct = $b['tier']->discount_type === 'percentage';
            if ($aPct !== $bPct) {
                return $aPct ? -1 : 1;
            }

            if ($aPct) {
                $aAmount = min((float) $a['tier']->discount_amount, 100.0);
                $bAmount = min((float) $b['tier']->discount_amount, 100.0);
                if ($aAmount !== $bAmount) {
                    return $bAmount <=> $aAmount;
                }
            }

            if ($a['tier']->min_people !== $b['tier']->min_people) {
                return $a['tier']->min_people <=> $b['tier']->min_people;
            }

            return $a['tier']->id <=> $b['tier']->id;
        });

        return $triggered[0];
    }
}
