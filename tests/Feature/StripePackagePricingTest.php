<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\PackageBasePricing;
use App\Models\PackageBlackoutDate;
use App\Models\PackagePriceVariation;
use App\Models\User;
use App\Services\PackagePricingService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StripePackagePricingTest extends TestCase
{
    use RefreshDatabase;

    private function makePackageWithVariations(
        array $variations = [['name' => 'Solo', 'regular_price' => 500, 'max_guests' => 2]],
        ?string $start = null,
        ?string $end = null,
        array $blackoutDates = [],
    ): Package {
        $package = Package::factory()->create();

        $base = PackageBasePricing::create([
            'package_id' => $package->id,
            'currency' => 'USD',
            'availability' => 'available',
            'start_date' => $start,
            'end_date' => $end,
        ]);

        foreach ($variations as $v) {
            PackagePriceVariation::create([
                'base_pricing_id' => $base->id,
                'name' => $v['name'],
                'regular_price' => $v['regular_price'],
                'sale_price' => $v['sale_price'] ?? $v['regular_price'],
                'max_guests' => $v['max_guests'],
                'description' => $v['description'] ?? null,
            ]);
        }

        foreach ($blackoutDates as $date) {
            PackageBlackoutDate::create([
                'base_pricing_id' => $base->id,
                'date' => $date,
                'reason' => 'test',
            ]);
        }

        return $package->fresh(['basePricing.variations', 'basePricing.blackoutDates']);
    }

    private function buildPackageOrderPayload(Package $package, array $overrides = []): array
    {
        return array_merge([
            'order_type' => 'package',
            'orderable_id' => $package->id,
            'travel_date' => now()->addDays(14)->format('Y-m-d'),
            'preferred_time' => '10:00 AM',
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'special_requirements' => 'None',
            'customer_email' => 'customer@example.com',
            'currency' => 'USD',
            'payment_intent_id' => 'pi_test_'.uniqid(),
            'emergency_contact' => [
                'name' => 'John Doe',
                'phone' => '+1234567890',
                'relationship' => 'Friend',
            ],
            'base_amount' => 500.00,
        ], $overrides);
    }

    public function test_service_returns_first_variation_when_variation_id_null(): void
    {
        $package = $this->makePackageWithVariations([
            ['name' => 'Solo', 'regular_price' => 500, 'max_guests' => 2],
            ['name' => 'Family', 'regular_price' => 1200, 'max_guests' => 6],
        ]);

        $price = app(PackagePricingService::class)->priceFor(
            $package, null, CarbonImmutable::now()->addDays(14), 1, 0, 0
        );

        $this->assertEquals(500.00, $price);
    }

    public function test_service_returns_named_variation_when_variation_id_supplied(): void
    {
        $package = $this->makePackageWithVariations([
            ['name' => 'Solo', 'regular_price' => 500, 'max_guests' => 2],
            ['name' => 'Family', 'regular_price' => 1200, 'max_guests' => 6],
        ]);
        $family = $package->basePricing->variations->where('name', 'Family')->first();

        $price = app(PackagePricingService::class)->priceFor(
            $package, $family->id, CarbonImmutable::now()->addDays(14), 4, 2, 0
        );

        $this->assertEquals(1200.00, $price);
    }

    public function test_service_throws_for_invalid_variation_id(): void
    {
        $package = $this->makePackageWithVariations();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('package_variation_invalid');

        app(PackagePricingService::class)->priceFor(
            $package, 999999, CarbonImmutable::now()->addDays(14), 1, 0, 0
        );
    }

    public function test_service_throws_when_package_has_no_pricing(): void
    {
        $package = Package::factory()->create();

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('package_pricing_missing');

        app(PackagePricingService::class)->priceFor(
            $package, null, CarbonImmutable::now()->addDays(14), 1, 0, 0
        );
    }

    public function test_service_throws_when_capacity_exceeded(): void
    {
        $package = $this->makePackageWithVariations([
            ['name' => 'Solo', 'regular_price' => 500, 'max_guests' => 2],
        ]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('package_capacity_exceeded');

        app(PackagePricingService::class)->priceFor(
            $package, null, CarbonImmutable::now()->addDays(14), 3, 0, 0
        );
    }

    public function test_service_throws_when_date_before_window(): void
    {
        $package = $this->makePackageWithVariations(
            start: now()->addDays(30)->format('Y-m-d'),
            end: now()->addDays(60)->format('Y-m-d'),
        );

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('package_date_out_of_window');

        app(PackagePricingService::class)->priceFor(
            $package, null, CarbonImmutable::now()->addDays(5), 1, 0, 0
        );
    }

    public function test_service_throws_on_blackout_date(): void
    {
        $blackout = now()->addDays(20)->format('Y-m-d');
        $package = $this->makePackageWithVariations(blackoutDates: [$blackout]);

        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('package_date_blackout');

        app(PackagePricingService::class)->priceFor(
            $package, null, CarbonImmutable::parse($blackout), 1, 0, 0
        );
    }

    public function test_create_order_overrides_tampered_base_amount_with_server_price(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $package = $this->makePackageWithVariations([
            ['name' => 'Solo', 'regular_price' => 500, 'max_guests' => 2],
        ]);

        $payload = $this->buildPackageOrderPayload($package, [
            'base_amount' => 100.00, // tampered — server should ignore
        ]);

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(200);
        $order = \App\Models\Order::with('payment')->latest('id')->first();
        $this->assertNotNull($order);
        $this->assertEquals(500.00, (float) $order->payment->total_amount);
    }

    public function test_create_order_returns_422_when_package_has_no_pricing(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $package = Package::factory()->create();

        $payload = $this->buildPackageOrderPayload($package);

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(422);
        $this->assertEquals('package_pricing_missing', $response->json('error'));
    }

    public function test_create_order_returns_422_for_invalid_variation_id(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $package = $this->makePackageWithVariations();

        $payload = $this->buildPackageOrderPayload($package, [
            'variation_id' => 999999,
        ]);

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(422);
        $this->assertEquals('package_variation_invalid', $response->json('error'));
    }

    public function test_create_order_returns_422_when_capacity_exceeded(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $package = $this->makePackageWithVariations([
            ['name' => 'Solo', 'regular_price' => 500, 'max_guests' => 2],
        ]);

        $payload = $this->buildPackageOrderPayload($package, [
            'number_of_adults' => 5,
        ]);

        $response = $this->postJson('/api/stripe/create-order', $payload);

        $response->assertStatus(422);
        $this->assertEquals('package_capacity_exceeded', $response->json('error'));
    }
}
