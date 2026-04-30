# Itinerary Pricing Model (unit-base)

## Concept
Itineraries no longer store a per-group price snapshot. Headcount is supplied at booking time and the total is recomputed from unit prices on the underlying activity/transfer rows. Migration `2026_04_29_090000_drop_pricing_inputs_from_itineraries_table.php` dropped `travel_date`, `adults`, `children`, `infants` from `itineraries`. Migration `2026_04_29_080001_add_extras_to_itinerary_transfers_table.php` added per-row `bag_count` and `waiting_minutes` to `itinerary_transfers`.

## Components
- Activity unit price = `ActivityPricing.regular_price` (per person)
- Transfer unit price = `Transfer::computeRoutePrice($headcount)` — returns per_person OR per_vehicle depending on `TransferPricingAvailability.price_type`
- Transfer extras = luggage (`extra_luggage_charge × bag_count`) + waiting (`waiting_charge × waiting_minutes`), both flat (not pax-multiplied)

## Formulas
```
headcount      = max(1, adults + children)        // infants excluded
per_pax_total  = Σ activity.regular_price + Σ transfer.computeRoutePrice(1) [per_person rows]
flat_total     = Σ transfer.computeRoutePrice(1) [per_vehicle rows] + Σ luggage + Σ waiting
total          = per_pax_total × headcount + flat_total
```
See `Itinerary.php:446-548` (`priceForGuests`, `pricingBreakdown`).

## Discount stack (activity only)
seasonal override → group discount → early bird → last minute → addons

In `ActivityDiscountService::quote` the three time/group reductions are computed independently against `subtotal = headcount × per_pax` and summed: `combined = group + early_bird + last_minute`; `final = max(0, subtotal − combined)`. Group tier is best-discount-wins (see `pickBest`). Early bird fires when `days_ahead ≥ days_before_start`; last minute fires when `0 ≤ days_ahead ≤ days_before_start`. Itinerary pricing does NOT apply this stack — discounts are an activity-booking-only path.

## Server enforcement (Stripe)
`StripeController::createOrder`:
- itinerary branch: recomputes via `priceForGuests($adults, $children)`, OVERRIDES client `amount`. Caps booking by `max_guests` first.
- activity branch: requires `base_amount`; validates it within 0.01 against `ActivityDiscountService::quote(...)['final_amount']`.
- transfer branch: recomputes `computeRoutePrice + luggage + waiting`; tolerance 0.01; rewrites `amount`, `base_amount`, `addons_amount`.
- Snapshot fields captured: `transfers_extras` (`itinerary_transfer_id`, `transfer_id`, `pax`, `bag_count`, `waiting_minutes`), `pricing` (priceVariations), `max_guests`. See `StripeController.php:24-300`.

## Capacity
`max_guests = min(transfer.schedule.maximum_passengers)` across all transfers in the itinerary (null when no transfer has a capacity set). Booking is rejected with `guests_exceed_transfer_capacity` if `adults + children > max_guests`.

## Worked example
2 adults + 1 child + 1 infant; 1 activity (`regular_price` 50); 1 per_person transfer (unit 20) with 1 bag (`extra_luggage_charge` 10/bag), 0 waiting:
```
headcount     = 3                 // infant excluded
per_pax_total = 50 + 20 = 70
flat_total    = 10
total         = 70 × 3 + 10 = 220
```

## Frontend consumers
- `ProductSidebar.jsx` — recomputes live from `pricing_breakdown`
- `SingleProductForm.jsx` — same recompute on submit

`PublicItineraryController` exposes `pricing_breakdown` only on the single-itinerary endpoint, not the list.

## See also
- `backend/app/Models/Itinerary.php:446-548`
- `backend/app/Http/Controllers/StripeController.php:24-300`
- `backend/app/Models/Transfer.php:199-258`
- `backend/app/Services/ActivityDiscountService.php`
- `backend/tests/Unit/ItineraryScheduleTotalPricingInputsTest.php`
