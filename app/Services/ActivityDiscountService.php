<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\ActivityGroupDiscount;
use RuntimeException;

final class ActivityDiscountService
{
    /**
     * @return array{
     *     headcount: int,
     *     per_pax: float,
     *     subtotal: float,
     *     selected_tier: ActivityGroupDiscount|null,
     *     complete_groups: int,
     *     discount_total: float,
     *     final_amount: float,
     *     currency: string|null,
     * }
     *
     * @throws RuntimeException when the activity has no pricing or regular_price.
     */
    public function quote(Activity $activity, int $headcount): array
    {
        $pricing = $activity->pricing;
        if (!$pricing || $pricing->regular_price === null) {
            throw new RuntimeException('Activity pricing is missing');
        }

        $perPax = (float) $pricing->regular_price;
        $currency = $pricing->currency ?? null;

        if ($headcount === 0) {
            return $this->emptyQuote(0, $perPax, 0.0, $currency);
        }

        $subtotal = round($headcount * $perPax, 2);
        $tiers = $this->loadTiersSorted($activity);
        $triggered = $this->triggeredTiers($tiers, $headcount, $perPax);

        if ($triggered === []) {
            return $this->emptyQuote($headcount, $perPax, $subtotal, $currency);
        }

        $best = $this->pickBest($triggered);
        $finalAmount = max(0.0, round($subtotal - $best['discount_total'], 2));

        return [
            'headcount' => $headcount,
            'per_pax' => $perPax,
            'subtotal' => $subtotal,
            'selected_tier' => $best['tier'],
            'complete_groups' => $best['complete_groups'],
            'discount_total' => $best['discount_total'],
            'final_amount' => $finalAmount,
            'currency' => $currency,
        ];
    }

    private function emptyQuote(int $headcount, float $perPax, float $subtotal, ?string $currency): array
    {
        return [
            'headcount' => $headcount,
            'per_pax' => $perPax,
            'subtotal' => $subtotal,
            'selected_tier' => null,
            'complete_groups' => 0,
            'discount_total' => 0.0,
            'final_amount' => $subtotal,
            'currency' => $currency,
        ];
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
