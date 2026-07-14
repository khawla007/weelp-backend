<?php

namespace Tests\Feature\Payment;

use App\Contracts\StripePaymentIntentGateway;
use App\Models\Activity;
use App\Models\ActivityLocation;
use App\Models\ActivityMediaGallery;
use App\Models\ActivityPricing;
use App\Models\City;
use App\Models\Country;
use App\Models\Media;
use App\Models\Order;
use App\Models\OrderPayment;
use App\Models\State;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CheckoutIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function selection(Activity $activity): array
    {
        return [
            'order_type' => 'activity',
            'orderable_id' => $activity->id,
            'travel_date' => now()->addWeek()->toDateString(),
            'preferred_time' => '10:00',
            'number_of_adults' => 2,
            'number_of_children' => 0,
            'addon_ids' => [],
        ];
    }

    private function createActivity(): Activity
    {
        $activity = Activity::factory()->create();
        ActivityPricing::factory()->create([
            'activity_id' => $activity->id,
            'regular_price' => 100,
            'currency' => 'USD',
        ]);

        return $activity;
    }

    public function test_initialize_payment_uses_server_quote_and_authenticated_user_metadata(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = $this->createActivity();
        $stripe = Mockery::mock(StripePaymentIntentGateway::class);
        $stripe->shouldReceive('create')->once()->withArgs(function (int $amount, string $currency, array $metadata) use ($user): bool {
            return $amount === 20000
                && $currency === 'usd'
                && $metadata['user_id'] === (string) $user->id
                && isset($metadata['selection_hash']);
        })->andReturn((object) [
            'id' => 'pi_test_initialize',
            'client_secret' => 'pi_test_initialize_secret_redacted',
        ]);
        $this->app->instance(StripePaymentIntentGateway::class, $stripe);

        $response = $this->postJson('/api/stripe/initialize-payment', $this->selection($activity) + [
            'amount' => 1,
            'currency' => 'EUR',
        ]);

        $response->assertOk()->assertJson([
            'success' => true,
            'paymentIntent' => 'pi_test_initialize',
            'quote' => ['amount' => 200, 'currency' => 'USD'],
        ]);
    }

    public function test_initialize_payment_uses_zero_decimal_currency_smallest_unit(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = Activity::factory()->create();
        ActivityPricing::factory()->create([
            'activity_id' => $activity->id,
            'regular_price' => 100,
            'currency' => 'JPY',
        ]);
        $stripe = Mockery::mock(StripePaymentIntentGateway::class);
        $stripe->shouldReceive('create')->once()->withArgs(
            static fn (int $amount, string $currency): bool => $amount === 100 && $currency === 'jpy'
        )->andReturn((object) [
            'id' => 'pi_test_jpy',
            'client_secret' => 'pi_test_jpy_secret_redacted',
        ]);
        $this->app->instance(StripePaymentIntentGateway::class, $stripe);

        $this->postJson('/api/stripe/initialize-payment', array_merge($this->selection($activity), [
            'number_of_adults' => 1,
        ]))->assertOk();
    }

    public function test_create_order_verifies_intent_and_ignores_client_prices(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = $this->createActivity();
        $country = Country::factory()->create(['name' => 'Snapshot Country']);
        $state = State::factory()->create(['country_id' => $country->id, 'name' => 'Snapshot State']);
        $city = City::factory()->create(['state_id' => $state->id, 'name' => 'Snapshot City']);
        ActivityLocation::create(['activity_id' => $activity->id, 'city_id' => $city->id, 'location_type' => 'primary']);
        $media = Media::create(['name' => 'Snapshot image', 'alt_text' => 'Snapshot alt', 'url' => 'checkout/snapshot.jpg']);
        ActivityMediaGallery::create(['activity_id' => $activity->id, 'media_id' => $media->id, 'is_featured' => true]);
        $selection = $this->selection($activity);
        $hash = hash('sha256', json_encode($selection, JSON_THROW_ON_ERROR));
        $stripe = Mockery::mock(StripePaymentIntentGateway::class);
        $stripe->shouldReceive('retrieve')->once()->andReturn((object) [
            'id' => 'pi_test_order',
            'amount' => 20000,
            'currency' => 'usd',
            'status' => 'requires_payment_method',
            'metadata' => (object) ['user_id' => (string) $user->id, 'selection_hash' => $hash],
        ]);
        $this->app->instance(StripePaymentIntentGateway::class, $stripe);

        $response = $this->postJson('/api/stripe/create-order', $selection + [
            'payment_intent_id' => 'pi_test_order',
            'amount' => 1,
            'base_amount' => 1,
            'addons_amount' => 999,
            'customer_email' => 'attacker@example.test',
            'emergency_contact' => ['name' => 'Contact', 'phone' => '+1234567890', 'relationship' => 'Friend'],
        ]);

        $response->assertOk()->assertJson(['success' => true]);
        $this->assertDatabaseHas('order_payments', [
            'payment_intent_id' => 'pi_test_order',
            'amount' => 200,
            'currency' => 'USD',
        ]);
        $snapshot = json_decode((string) Order::firstOrFail()->item_snapshot_json, true, flags: JSON_THROW_ON_ERROR);
        $this->assertSame('Snapshot City', $snapshot['location'][0]['city']);
        $this->assertSame('Snapshot Country', $snapshot['location'][0]['country']);
        $this->assertSame('Snapshot image', $snapshot['media'][0]['name']);
        $this->assertSame('Snapshot alt', $snapshot['media'][0]['alt']);
    }

    public function test_create_order_rejects_mismatched_intent_without_orphans(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = $this->createActivity();
        $stripe = Mockery::mock(StripePaymentIntentGateway::class);
        $stripe->shouldReceive('retrieve')->once()->andReturn((object) [
            'id' => 'pi_test_bad',
            'amount' => 1,
            'currency' => 'usd',
            'status' => 'requires_payment_method',
            'metadata' => (object) ['user_id' => (string) $user->id, 'selection_hash' => 'wrong'],
        ]);
        $this->app->instance(StripePaymentIntentGateway::class, $stripe);

        $response = $this->postJson('/api/stripe/create-order', $this->selection($activity) + [
            'payment_intent_id' => 'pi_test_bad',
            'emergency_contact' => ['name' => 'Contact', 'phone' => '+1234567890', 'relationship' => 'Friend'],
        ]);

        $response->assertUnprocessable()->assertJson(['error' => 'payment_intent_mismatch']);
        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderPayment::count());
    }

    public static function invalidIntentProvider(): array
    {
        return [
            'wrong currency' => [['currency' => 'eur']],
            'wrong authenticated owner' => [['metadata_user' => '999999']],
            'cancelled status' => [['status' => 'canceled']],
        ];
    }

    #[DataProvider('invalidIntentProvider')]
    public function test_create_order_rejects_invalid_intent_attributes(array $override): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = $this->createActivity();
        $selection = $this->selection($activity);
        $hash = hash('sha256', json_encode($selection, JSON_THROW_ON_ERROR));
        $stripe = Mockery::mock(StripePaymentIntentGateway::class);
        $stripe->shouldReceive('retrieve')->once()->andReturn((object) [
            'id' => 'pi_test_invalid_attribute',
            'amount' => 20000,
            'currency' => $override['currency'] ?? 'usd',
            'status' => $override['status'] ?? 'requires_payment_method',
            'metadata' => (object) [
                'user_id' => $override['metadata_user'] ?? (string) $user->id,
                'selection_hash' => $hash,
            ],
        ]);
        $this->app->instance(StripePaymentIntentGateway::class, $stripe);

        $response = $this->postJson('/api/stripe/create-order', $selection + [
            'payment_intent_id' => 'pi_test_invalid_attribute',
            'emergency_contact' => ['name' => 'Contact', 'phone' => '+1234567890', 'relationship' => 'Friend'],
        ]);

        $response->assertUnprocessable()->assertJson(['error' => 'payment_intent_mismatch']);
        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderPayment::count());
    }

    public function test_identical_retry_returns_existing_order_without_duplicate_writes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = $this->createActivity();
        $selection = $this->selection($activity);
        $hash = hash('sha256', json_encode($selection, JSON_THROW_ON_ERROR));
        $stripe = Mockery::mock(StripePaymentIntentGateway::class);
        $stripe->shouldReceive('retrieve')->once()->andReturn((object) [
            'id' => 'pi_test_replay', 'amount' => 20000, 'currency' => 'usd',
            'status' => 'requires_payment_method',
            'metadata' => (object) ['user_id' => (string) $user->id, 'selection_hash' => $hash],
        ]);
        $this->app->instance(StripePaymentIntentGateway::class, $stripe);
        $payload = $selection + [
            'payment_intent_id' => 'pi_test_replay',
            'emergency_contact' => ['name' => 'Contact', 'phone' => '+1234567890', 'relationship' => 'Friend'],
        ];

        $first = $this->postJson('/api/stripe/create-order', $payload);
        $second = $this->postJson('/api/stripe/create-order', $payload);

        $second->assertOk()->assertJson(['order_id' => $first->json('order_id'), 'idempotent' => true]);
        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderPayment::count());
    }

    public function test_retry_with_changed_selection_is_rejected_without_duplicate_writes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = $this->createActivity();
        $selection = $this->selection($activity);
        $hash = hash('sha256', json_encode($selection, JSON_THROW_ON_ERROR));
        $stripe = Mockery::mock(StripePaymentIntentGateway::class);
        $stripe->shouldReceive('retrieve')->once()->andReturn((object) [
            'id' => 'pi_test_changed', 'amount' => 20000, 'currency' => 'usd',
            'status' => 'requires_payment_method',
            'metadata' => (object) ['user_id' => (string) $user->id, 'selection_hash' => $hash],
        ]);
        $this->app->instance(StripePaymentIntentGateway::class, $stripe);
        $payload = $selection + [
            'payment_intent_id' => 'pi_test_changed',
            'emergency_contact' => ['name' => 'Contact', 'phone' => '+1234567890', 'relationship' => 'Friend'],
        ];
        $this->postJson('/api/stripe/create-order', $payload)->assertOk();

        $response = $this->postJson('/api/stripe/create-order', array_merge($payload, ['number_of_adults' => 3]));

        $response->assertUnprocessable()->assertJson(['error' => 'payment_intent_mismatch']);
        $this->assertSame(1, Order::count());
        $this->assertSame(1, OrderPayment::count());
    }

    public function test_payment_insert_failure_rolls_back_every_order_write_and_returns_safe_error(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user, 'api');
        $activity = $this->createActivity();
        $selection = $this->selection($activity);
        $hash = hash('sha256', json_encode($selection, JSON_THROW_ON_ERROR));
        $stripe = Mockery::mock(StripePaymentIntentGateway::class);
        $stripe->shouldReceive('retrieve')->once()->andReturn((object) [
            'id' => 'pi_test_rollback', 'amount' => 20000, 'currency' => 'usd',
            'status' => 'requires_payment_method',
            'metadata' => (object) ['user_id' => (string) $user->id, 'selection_hash' => $hash],
        ]);
        $this->app->instance(StripePaymentIntentGateway::class, $stripe);
        Event::listen('eloquent.creating: '.OrderPayment::class, static function (): void {
            throw new \RuntimeException('sensitive database detail');
        });

        $response = $this->postJson('/api/stripe/create-order', $selection + [
            'payment_intent_id' => 'pi_test_rollback',
            'emergency_contact' => ['name' => 'Contact', 'phone' => '+1234567890', 'relationship' => 'Friend'],
        ]);

        $response->assertStatus(500)->assertExactJson([
            'success' => false,
            'error' => 'order_creation_failed',
            'message' => 'The order could not be created.',
        ]);
        $this->assertSame(0, Order::count());
        $this->assertSame(0, OrderPayment::count());
    }
}
