<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\City;
use App\Models\Itinerary;
use App\Models\ItineraryActivity;
use App\Models\ItineraryBasePricing;
use App\Models\ItineraryLocation;
use App\Models\ItineraryMediaGallery;
use App\Models\ItineraryMeta;
use App\Models\ItineraryPriceVariation;
use App\Models\ItinerarySchedule;
use App\Models\Media;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CreatorExploreFixtureSeeder extends Seeder
{
    private const FIXTURE_SLUGS = [
        'creator-dubai-weekend',
        'creator-paris-food-map',
        'creator-marseille-coast-days',
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            return;
        }

        DB::transaction(function (): void {
            $this->purgeExistingFixtures();

            $creators = [
                $this->creator('Nora Field Notes', 'creator.nora@weelp.test'),
                $this->creator('Samir City Walks', 'creator.samir@weelp.test'),
            ];

            $activityIds = Activity::query()->orderBy('id')->limit(6)->pluck('id')->values();
            $mediaIds = Media::query()->orderBy('id')->limit(9)->pluck('id')->values();

            $fixtures = [
                [
                    'name' => 'Creator Dubai Weekend',
                    'slug' => 'creator-dubai-weekend',
                    'description' => 'A compact creator-led Dubai itinerary with skyline stops, old-town food, and an evening desert plan.',
                    'city_slug' => 'dubai',
                    'creator' => $creators[0],
                    'likes' => 42,
                    'views' => 860,
                    'days' => 2,
                    'prices' => [165.00, 120.00],
                    'currency' => 'AED',
                ],
                [
                    'name' => 'Creator Paris Food Map',
                    'slug' => 'creator-paris-food-map',
                    'description' => 'A food-first Paris route built around markets, bakeries, and easy neighborhood walks.',
                    'city_slug' => 'paris',
                    'creator' => $creators[1],
                    'likes' => 18,
                    'views' => 310,
                    'days' => 3,
                    'prices' => [95.00, 110.00, 80.00],
                    'currency' => 'EUR',
                ],
                [
                    'name' => 'Creator Marseille Coast Days',
                    'slug' => 'creator-marseille-coast-days',
                    'description' => 'A slower Marseille itinerary with coastal viewpoints, neighborhood lunches, and flexible half-day pacing.',
                    'city_slug' => 'marseille',
                    'creator' => $creators[0],
                    'likes' => 7,
                    'views' => 95,
                    'days' => 2,
                    'prices' => [75.00, 65.00],
                    'currency' => 'EUR',
                ],
            ];

            foreach ($fixtures as $index => $fixture) {
                $this->createFixtureItinerary($fixture, $activityIds, $mediaIds, $index);
            }
        });
    }

    private function creator(string $name, string $email): User
    {
        $user = User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make('password'),
                'role' => 'customer',
            ],
        );

        $user->forceFill([
            'name' => $name,
            'role' => 'customer',
            'is_creator' => true,
            'status' => 'active',
        ])->save();

        return $user;
    }

    private function createFixtureItinerary(array $fixture, $activityIds, $mediaIds, int $fixtureIndex): void
    {
        $city = City::query()
            ->where('slug', $fixture['city_slug'])
            ->first()
            ?? City::query()->orderBy('id')->first();

        if (! $city) {
            return;
        }

        $itinerary = Itinerary::query()->create([
            'name' => $fixture['name'],
            'slug' => $fixture['slug'],
            'description' => $fixture['description'],
            'featured_itinerary' => true,
            'private_itinerary' => false,
        ]);

        ItineraryMeta::query()->create([
            'itinerary_id' => $itinerary->id,
            'creator_id' => $fixture['creator']->id,
            'user_id' => $fixture['creator']->id,
            'status' => 'approved',
            'views_count' => $fixture['views'],
            'likes_count' => $fixture['likes'],
        ]);

        ItineraryLocation::query()->create([
            'itinerary_id' => $itinerary->id,
            'city_id' => $city->id,
        ]);

        foreach (range(1, $fixture['days']) as $day) {
            $schedule = ItinerarySchedule::query()->create([
                'itinerary_id' => $itinerary->id,
                'day' => $day,
                'title' => 'Day '.$day,
            ]);

            if ($activityIds->isNotEmpty()) {
                ItineraryActivity::query()->create([
                    'schedule_id' => $schedule->id,
                    'activity_id' => $activityIds[($fixtureIndex + $day - 1) % $activityIds->count()],
                    'start_time' => '09:00:00',
                    'end_time' => '12:00:00',
                    'notes' => 'Creator fixture activity',
                    'price' => $fixture['prices'][$day - 1] ?? 75.00,
                    'included' => true,
                ]);
            }
        }

        $basePricing = ItineraryBasePricing::query()->create([
            'itinerary_id' => $itinerary->id,
            'currency' => $fixture['currency'],
            'availability' => 'Available',
            'start_date' => now()->toDateString(),
            'end_date' => now()->addMonths(3)->toDateString(),
        ]);

        ItineraryPriceVariation::query()->create([
            'base_pricing_id' => $basePricing->id,
            'name' => 'Creator fixture standard',
            'regular_price' => array_sum($fixture['prices']),
            'sale_price' => null,
            'max_guests' => 4,
            'description' => 'Representative local fixture for creator explore verification.',
        ]);

        foreach ($mediaIds->slice($fixtureIndex * 2, 2)->values() as $mediaIndex => $mediaId) {
            ItineraryMediaGallery::query()->create([
                'itinerary_id' => $itinerary->id,
                'media_id' => $mediaId,
                'is_featured' => $mediaIndex === 0,
            ]);
        }
    }

    private function purgeExistingFixtures(): void
    {
        Itinerary::query()
            ->whereIn('slug', self::FIXTURE_SLUGS)
            ->get()
            ->each(function (Itinerary $itinerary): void {
                $scheduleIds = $itinerary->schedules()->pluck('id');
                ItineraryActivity::query()->whereIn('schedule_id', $scheduleIds)->delete();
                ItinerarySchedule::query()->whereIn('id', $scheduleIds)->delete();

                $basePricingIds = $itinerary->basePricing()->pluck('id');
                ItineraryPriceVariation::query()->whereIn('base_pricing_id', $basePricingIds)->delete();
                ItineraryBasePricing::query()->whereIn('id', $basePricingIds)->delete();

                $itinerary->locations()->delete();
                $itinerary->mediaGallery()->delete();
                $itinerary->meta()->delete();
                $itinerary->delete();
            });
    }
}
