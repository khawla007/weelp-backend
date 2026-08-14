<?php

namespace Tests\Feature\Payment;

use App\Contracts\StripePaymentIntentGateway;
use App\Models\Activity;
use App\Models\ActivityPricing;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItineraryBasePricing;
use App\Models\ItineraryMeta;
use App\Models\ItinerarySchedule;
use App\Models\User;
use App\Services\CreatorItineraryLifecycleService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ItineraryCheckoutVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_initialization_rejects_a_restored_draft_itinerary(): void
    {
        $user = User::factory()->customer()->create();
        $itinerary = $this->restoredDraft();
        $gateway = Mockery::mock(StripePaymentIntentGateway::class);
        $gateway->shouldNotReceive('create');
        $this->app->instance(StripePaymentIntentGateway::class, $gateway);

        $this->actingAs($user, 'api')
            ->postJson('/api/stripe/initialize-payment', $this->selection($itinerary))
            ->assertNotFound()
            ->assertJsonPath('error', 'item_unavailable');
    }

    public function test_order_creation_rejects_a_restored_draft_before_stripe_lookup(): void
    {
        $user = User::factory()->customer()->create();
        $itinerary = $this->restoredDraft();
        $gateway = Mockery::mock(StripePaymentIntentGateway::class);
        $gateway->shouldNotReceive('retrieve');
        $this->app->instance(StripePaymentIntentGateway::class, $gateway);

        $this->actingAs($user, 'api')
            ->postJson('/api/stripe/create-order', $this->selection($itinerary) + [
                'payment_intent_id' => 'pi_hidden_itinerary',
                'emergency_contact' => [
                    'name' => 'Test Contact',
                    'phone' => '+15555550123',
                    'relationship' => 'Friend',
                ],
            ])
            ->assertNotFound()
            ->assertJsonPath('error', 'item_unavailable');
    }

    public function test_legacy_checkout_session_rejects_a_restored_draft_itinerary(): void
    {
        $user = User::factory()->customer()->create();
        $itinerary = $this->restoredDraft();

        $this->actingAs($user, 'api')
            ->postJson('/api/create-checkout-session', $this->selection($itinerary) + [
                'special_requirements' => null,
                'customer_email' => $user->email,
                'currency' => 'USD',
                'emergency_contact' => [
                    'name' => 'Test Contact',
                    'phone' => '+15555550123',
                    'relationship' => 'Friend',
                ],
            ])
            ->assertNotFound();
    }

    public function test_private_copy_payment_initialization_allows_only_its_owner(): void
    {
        $owner = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $itinerary = $this->privateCopy($owner);
        $gateway = Mockery::mock(StripePaymentIntentGateway::class);
        $gateway->shouldReceive('create')->once()->andReturn((object) [
            'id' => 'pi_private_owner',
            'client_secret' => 'pi_private_owner_secret',
        ]);
        $this->app->instance(StripePaymentIntentGateway::class, $gateway);

        $this->actingAs($owner, 'api')
            ->postJson('/api/stripe/initialize-payment', $this->selection($itinerary))
            ->assertOk()
            ->assertJsonPath('quote.amount', 80);

        $this->actingAs($other, 'api')
            ->postJson('/api/stripe/initialize-payment', $this->selection($itinerary))
            ->assertNotFound()
            ->assertJsonPath('error', 'item_unavailable');
    }

    public function test_private_copy_order_creation_succeeds_for_its_owner(): void
    {
        $owner = User::factory()->customer()->create();
        $itinerary = $this->privateCopy($owner);
        $selection = $this->selection($itinerary);
        $gateway = Mockery::mock(StripePaymentIntentGateway::class);
        $gateway->shouldReceive('retrieve')->once()->andReturn($this->paymentIntent($owner, $selection, 'pi_private_order'));
        $this->app->instance(StripePaymentIntentGateway::class, $gateway);

        $this->actingAs($owner, 'api')
            ->postJson('/api/stripe/create-order', $selection + $this->orderDetails('pi_private_order'))
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_private_copy_order_creation_rechecks_ownership_inside_the_transaction(): void
    {
        $owner = User::factory()->customer()->create();
        $other = User::factory()->customer()->create();
        $itinerary = $this->privateCopy($owner);
        $selection = $this->selection($itinerary);
        $gateway = Mockery::mock(StripePaymentIntentGateway::class);
        $gateway->shouldReceive('retrieve')->once()->andReturnUsing(function () use ($itinerary, $other, $owner, $selection) {
            $itinerary->meta()->update(['user_id' => $other->id]);

            return $this->paymentIntent($owner, $selection, 'pi_private_recheck');
        });
        $this->app->instance(StripePaymentIntentGateway::class, $gateway);

        $this->actingAs($owner, 'api')
            ->postJson('/api/stripe/create-order', $selection + $this->orderDetails('pi_private_recheck'))
            ->assertNotFound()
            ->assertJsonPath('error', 'item_unavailable');
    }

    private function restoredDraft(): Itinerary
    {
        $creator = User::factory()->creator()->create();
        $itinerary = Itinerary::factory()->create();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        ItineraryBasePricing::create([
            'itinerary_id' => $itinerary->id,
            'currency' => 'USD',
            'availability' => 'year_round',
        ]);
        $service = app(CreatorItineraryLifecycleService::class);
        $service->trash($itinerary->id);

        return $service->restoreToDraft($itinerary->id, $creator->id);
    }

    private function privateCopy(User $owner): Itinerary
    {
        $itinerary = Itinerary::factory()->create(['private_itinerary' => true]);
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'user_id' => $owner->id,
            'parent_itinerary_id' => Itinerary::factory()->create()->id,
        ]);
        $schedule = ItinerarySchedule::factory()->create(['itinerary_id' => $itinerary->id]);
        $activity = Activity::factory()->create();
        ActivityPricing::factory()->create(['activity_id' => $activity->id, 'regular_price' => 80, 'currency' => 'USD']);
        ItineraryActivity::factory()->create(['schedule_id' => $schedule->id, 'activity_id' => $activity->id]);

        return $itinerary;
    }

    private function paymentIntent(User $user, array $selection, string $id): object
    {
        return (object) [
            'id' => $id,
            'amount' => 8000,
            'currency' => 'usd',
            'status' => 'requires_payment_method',
            'metadata' => (object) [
                'user_id' => (string) $user->id,
                'selection_hash' => hash('sha256', json_encode($selection, JSON_THROW_ON_ERROR)),
            ],
        ];
    }

    private function orderDetails(string $paymentIntentId): array
    {
        return [
            'payment_intent_id' => $paymentIntentId,
            'emergency_contact' => [
                'name' => 'Test Contact',
                'phone' => '+15555550123',
                'relationship' => 'Friend',
            ],
        ];
    }

    private function selection(Itinerary $itinerary): array
    {
        return [
            'order_type' => 'itinerary',
            'orderable_id' => $itinerary->id,
            'travel_date' => now()->addWeek()->toDateString(),
            'preferred_time' => '10:00',
            'number_of_adults' => 1,
            'number_of_children' => 0,
        ];
    }
}
