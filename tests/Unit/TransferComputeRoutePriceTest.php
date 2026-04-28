<?php

namespace Tests\Unit;

use App\Models\Transfer;
use App\Models\TransferPricingAvailability;
use App\Models\TransferZonePrice;
use PHPUnit\Framework\TestCase;

class TransferComputeRoutePriceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Transfer::clearZonePriceCache();
    }

    protected function tearDown(): void
    {
        Transfer::clearZonePriceCache();
        parent::tearDown();
    }

    /**
     * Test that route_price sums zone base_price (40) + transfer_price (25) = 65
     * and routeCurrency returns 'USD' from the zone price.
     */
    public function test_route_price_sums_zone_base_and_transfer_price(): void
    {
        // Create real model instances without database
        $zonePricing = new TransferZonePrice([
            'base_price' => 40.00,
            'currency'   => 'USD',
        ]);

        $pricingAvailability = new TransferPricingAvailability([
            'is_vendor'      => false,
            'transfer_price' => 25.00,
            'currency'       => 'EUR',  // Should be ignored in favor of zone currency
        ]);

        // Create transfer and use reflection to inject the mocked zone price
        $transfer = new Transfer();
        $refClass = new \ReflectionClass($transfer);
        $resolvedProperty = $refClass->getProperty('resolvedZonePrice');
        $resolvedProperty->setAccessible(true);
        $resolvedProperty->setValue($transfer, $zonePricing);

        // Set pricingAvailability as a relation (circumvent lazy loading)
        $transfer->setRelation('pricingAvailability', $pricingAvailability);

        // Assert computeRoutePrice() == 65.00
        $this->assertEquals(65.00, $transfer->computeRoutePrice());

        // Assert routeCurrency() == 'USD' (from zone price, not PA)
        $this->assertSame('USD', $transfer->routeCurrency());
    }

    /**
     * Test that vendor pricing (is_vendor=true) contributes zero transfer_price
     * so when route is null, computeRoutePrice returns 0.0.
     */
    public function test_vendor_pricing_contributes_zero_transfer_price(): void
    {
        // Create vendor pricing availability (is_vendor=true, so transfer_price ignored)
        $pricingAvailability = new TransferPricingAvailability([
            'is_vendor'      => true,
            'transfer_price' => 999.99,  // This should be ignored
            'currency'       => 'USD',
        ]);

        $transfer = new Transfer();

        // Set route relation to null to prevent lazy loading
        $transfer->setRelation('route', null);

        // Use reflection to set resolvedZonePrice to null
        $refClass = new \ReflectionClass($transfer);
        $resolvedProperty = $refClass->getProperty('resolvedZonePrice');
        $resolvedProperty->setAccessible(true);
        $resolvedProperty->setValue($transfer, null);

        // Set pricingAvailability relation
        $transfer->setRelation('pricingAvailability', $pricingAvailability);

        // Assert computeRoutePrice() == 0.0 (no zone, vendor pricing ignored)
        $this->assertEquals(0.0, $transfer->computeRoutePrice());

        // Assert routeCurrency() == null (no zone price, vendor is_vendor=true)
        $this->assertNull($transfer->routeCurrency());
    }

    /**
     * Test that when transfer has null route and no non-vendor PA,
     * computeRoutePrice returns 0.0.
     */
    public function test_missing_route_and_pricing_returns_zero(): void
    {
        $transfer = new Transfer();

        // Set route relation to null to prevent lazy loading
        $transfer->setRelation('route', null);

        // Use reflection to set resolvedZonePrice to null
        $refClass = new \ReflectionClass($transfer);
        $resolvedProperty = $refClass->getProperty('resolvedZonePrice');
        $resolvedProperty->setAccessible(true);
        $resolvedProperty->setValue($transfer, null);

        // Set pricingAvailability relation to null to prevent lazy loading
        $transfer->setRelation('pricingAvailability', null);

        // Assert computeRoutePrice() == 0.0
        $this->assertEquals(0.0, $transfer->computeRoutePrice());

        // Assert routeCurrency() == null
        $this->assertNull($transfer->routeCurrency());
    }

    /**
     * Test that routeCurrency checks zone price currency is non-null before returning,
     * falling back to non-vendor pricing availability currency if zone currency is null.
     */
    public function test_route_currency_falls_back_when_zone_currency_is_null(): void
    {
        // Create zone price with null currency
        $zonePricing = new TransferZonePrice([
            'base_price' => 40.00,
            'currency'   => null,  // Null currency — should not be returned
        ]);

        // Create non-vendor pricing availability with valid currency
        $pricingAvailability = new TransferPricingAvailability([
            'is_vendor'      => false,
            'transfer_price' => 25.00,
            'currency'       => 'CAD',  // Should be returned as fallback
        ]);

        $transfer = new Transfer();

        // Use reflection to inject the zone price with null currency
        $refClass = new \ReflectionClass($transfer);
        $resolvedProperty = $refClass->getProperty('resolvedZonePrice');
        $resolvedProperty->setAccessible(true);
        $resolvedProperty->setValue($transfer, $zonePricing);

        // Set pricingAvailability relation
        $transfer->setRelation('pricingAvailability', $pricingAvailability);

        // Assert routeCurrency() == 'CAD' (falls back because zone currency is null)
        $this->assertSame('CAD', $transfer->routeCurrency());
    }

    /**
     * route_price is now zone base + transfer price ONLY (luggage and waiting are
     * per-bag / per-minute rates, exposed separately and added at order time).
     */
    public function test_route_price_excludes_luggage_and_waiting_rates(): void
    {
        $zonePricing = new TransferZonePrice([
            'base_price' => 100.00,
            'currency'   => 'USD',
        ]);

        $pricingAvailability = new TransferPricingAvailability([
            'is_vendor'              => false,
            'transfer_price'         => 50.00,
            'extra_luggage_charge'   => 10.00, // now meaning: $10 per bag
            'waiting_charge'         => 5.00,  // now meaning: $5 per minute
            'currency'               => 'USD',
        ]);

        $transfer = new Transfer();

        $refClass = new \ReflectionClass($transfer);
        $resolvedProperty = $refClass->getProperty('resolvedZonePrice');
        $resolvedProperty->setAccessible(true);
        $resolvedProperty->setValue($transfer, $zonePricing);

        $transfer->setRelation('pricingAvailability', $pricingAvailability);

        $this->assertEquals(150.00, $transfer->computeRoutePrice());
        $this->assertEquals(10.00, $transfer->luggagePerBagRate());
        $this->assertEquals(5.00, $transfer->waitingPerMinuteRate());
    }

    /**
     * Test that null extras coerce to 0, so zone (50) + transfer (30) + null + null = 80
     */
    public function test_route_price_null_extras_coerce_to_zero(): void
    {
        // Create zone price
        $zonePricing = new TransferZonePrice([
            'base_price' => 50.00,
            'currency'   => 'USD',
        ]);

        // Create non-vendor pricing availability with null extras
        $pricingAvailability = new TransferPricingAvailability([
            'is_vendor'              => false,
            'transfer_price'         => 30.00,
            'extra_luggage_charge'   => null,
            'waiting_charge'         => null,
            'currency'               => 'USD',
        ]);

        $transfer = new Transfer();

        // Use reflection to inject the zone price
        $refClass = new \ReflectionClass($transfer);
        $resolvedProperty = $refClass->getProperty('resolvedZonePrice');
        $resolvedProperty->setAccessible(true);
        $resolvedProperty->setValue($transfer, $zonePricing);

        // Set pricingAvailability relation
        $transfer->setRelation('pricingAvailability', $pricingAvailability);

        // Assert computeRoutePrice() == 80.00 (50 + 30 + 0 + 0)
        $this->assertEquals(80.00, $transfer->computeRoutePrice());
    }
}
