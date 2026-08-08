<?php

namespace Tests\Feature\Payment;

use App\Contracts\StripePaymentIntentGateway;
use App\Models\Itinerary;
use App\Models\ItineraryBasePricing;
use App\Models\ItineraryMeta;
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
