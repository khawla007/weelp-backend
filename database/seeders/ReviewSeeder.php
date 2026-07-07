<?php

namespace Database\Seeders;

use App\Models\Activity;
use App\Models\City;
use App\Models\Itinerary;
use App\Models\ItineraryLocation;
use App\Models\Media;
use App\Models\Review;
use App\Models\ReviewMediaGallery;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        ReviewMediaGallery::truncate();
        Review::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        $customers = User::where('role', 'customer')->orderBy('id')->get();

        if ($customers->isEmpty()) {
            $this->command?->warn('ReviewSeeder skipped: no customer users found.');
            return;
        }

        $dubai = City::where('slug', 'dubai')->first();

        if (! $dubai) {
            $this->command?->warn('ReviewSeeder skipped: Dubai city not found.');
            return;
        }

        $this->ensureDubaiLocations($dubai);

        $mediaIds = Media::orderBy('id')->limit(8)->pluck('id')->values();
        $baseDate = Carbon::parse('2026-07-07 10:00:00');

        $reviewRows = [
            [
                'type' => 'activity',
                'slug' => 'dubai-desert-safari-bbq',
                'user_index' => 0,
                'rating' => 5,
                'featured' => true,
                'days_ago' => 1,
                'text' => 'The desert safari was paced well, the pickup was on time, and the evening camp felt worth the booking.',
            ],
            [
                'type' => 'activity',
                'slug' => 'dubai-desert-safari-bbq',
                'user_index' => 1,
                'rating' => 5,
                'featured' => false,
                'days_ago' => 2,
                'text' => 'Dune bashing, dinner, and the show all worked smoothly. I would book this again with family.',
            ],
            [
                'type' => 'activity',
                'slug' => 'burj-khalifa-at-the-top',
                'user_index' => 2,
                'rating' => 5,
                'featured' => true,
                'days_ago' => 3,
                'text' => 'The entry timing was clear and the view from the top was exactly what we hoped for.',
            ],
            [
                'type' => 'activity',
                'slug' => 'dubai-marina-yacht-cruise',
                'user_index' => 3,
                'rating' => 5,
                'featured' => false,
                'days_ago' => 4,
                'text' => 'Clean yacht, helpful crew, and a relaxed route through the marina skyline.',
            ],
            [
                'type' => 'activity',
                'slug' => 'palm-jumeirah-jet-ski',
                'user_index' => 4,
                'rating' => 4,
                'featured' => false,
                'days_ago' => 5,
                'text' => 'The guide gave good instructions and the route had strong photo stops.',
            ],
            [
                'type' => 'activity',
                'slug' => 'dubai-mall-aquarium',
                'user_index' => 5,
                'rating' => 4,
                'featured' => false,
                'days_ago' => 6,
                'text' => 'Easy entry and a good indoor option when the afternoon heat was too much.',
            ],
            [
                'type' => 'activity',
                'slug' => 'dubai-creek-dhow-cruise',
                'user_index' => 0,
                'rating' => 4,
                'featured' => false,
                'days_ago' => 7,
                'text' => 'The creek cruise was calm, the dinner was decent, and the old-city views were memorable.',
            ],
            [
                'type' => 'itinerary',
                'slug' => 'adventure-tour-in-dubai',
                'user_index' => 1,
                'rating' => 5,
                'featured' => false,
                'days_ago' => 8,
                'text' => 'The itinerary balanced activities and breaks better than planning each stop ourselves.',
            ],
            [
                'type' => 'itinerary',
                'slug' => 'adventure-tour-in-dubai',
                'user_index' => 2,
                'rating' => 4,
                'featured' => false,
                'days_ago' => 9,
                'text' => 'A useful Dubai plan for a short trip, especially with transfers and timings already handled.',
            ],
            [
                'type' => 'activity',
                'slug' => 'burj-khalifa-at-the-top',
                'user_index' => 5,
                'rating' => 3,
                'featured' => false,
                'days_ago' => 12,
                'text' => 'The visit was good overall, though the busiest viewing area needed more time than expected.',
            ],
        ];

        $reviewRows = array_merge(
            $reviewRows,
            $this->makeBulkReviews(
                type: 'activity',
                slug: 'dubai-desert-safari-bbq',
                startDay: 13,
                textPrefix: 'Desert safari test review'
            ),
            $this->makeBulkReviews(
                type: 'itinerary',
                slug: 'adventure-tour-in-dubai',
                startDay: 25,
                textPrefix: 'Dubai itinerary test review'
            )
        );

        $mediaReviewCounts = [
            'activity:dubai-desert-safari-bbq' => 0,
            'itinerary:adventure-tour-in-dubai' => 0,
        ];

        foreach ($reviewRows as $index => $row) {
            $item = $this->findItem($row['type'], $row['slug']);

            if (! $item) {
                $this->command?->warn("ReviewSeeder skipped {$row['type']} {$row['slug']}: item not found.");
                continue;
            }

            $review = Review::create([
                'user_id' => $customers[$row['user_index'] % $customers->count()]->id,
                'order_id' => null,
                'item_type' => $row['type'],
                'item_id' => $item->id,
                'item_name_snapshot' => $item->name,
                'item_slug_snapshot' => $item->slug,
                'rating' => $row['rating'],
                'review_text' => $row['text'],
                'status' => 'approved',
                'is_featured' => $row['featured'],
                'created_at' => $baseDate->copy()->subDays($row['days_ago']),
                'updated_at' => $baseDate->copy()->subDays($row['days_ago']),
            ]);

            $mediaKey = "{$row['type']}:{$row['slug']}";

            if ($mediaIds->isNotEmpty() && array_key_exists($mediaKey, $mediaReviewCounts) && $mediaReviewCounts[$mediaKey] < 6) {
                ReviewMediaGallery::create([
                    'review_id' => $review->id,
                    'media_id' => $mediaIds[$index % $mediaIds->count()],
                    'sort_order' => 0,
                ]);

                $mediaReviewCounts[$mediaKey]++;
            }
        }
    }

    private function makeBulkReviews(string $type, string $slug, int $startDay, string $textPrefix): array
    {
        return collect(range(1, 10))
            ->map(fn (int $number) => [
                'type' => $type,
                'slug' => $slug,
                'user_index' => $number,
                'rating' => $number <= 6 ? 5 : 4,
                'featured' => false,
                'days_ago' => $startDay + $number,
                'text' => "{$textPrefix} {$number}: clear timing, useful support, and a booking flow that made the trip easier to manage.",
            ])
            ->all();
    }

    private function ensureDubaiLocations(City $dubai): void
    {
        $itinerary = Itinerary::where('slug', 'adventure-tour-in-dubai')->first();

        if ($itinerary) {
            ItineraryLocation::updateOrCreate(
                ['itinerary_id' => $itinerary->id],
                ['city_id' => $dubai->id]
            );
        }
    }

    private function findItem(string $type, string $slug): Activity|Itinerary|null
    {
        return match ($type) {
            'activity' => Activity::where('slug', $slug)->first(),
            'itinerary' => Itinerary::where('slug', $slug)->first(),
            default => null,
        };
    }
}
