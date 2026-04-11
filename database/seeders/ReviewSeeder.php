<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Review;
use App\Models\ReviewMediaGallery;
use App\Models\Media;
use Illuminate\Support\Arr;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        $itemTypes = ['activity', 'itinerary', 'package', 'transfer'];
        $statuses = ['approved', 'pending'];
        $mediaIds = Media::pluck('id')->toArray();

        for ($i = 1; $i <= 11; $i++) {
            $review = Review::create([
                'user_id'     => rand(1, 5),
                'item_type'   => $itemTypes[array_rand($itemTypes)],
                'item_id'     => rand(1, 10),
                'rating'      => rand(1, 5),
                'review_text' => fake()->sentence(12),
                'status'      => $statuses[array_rand($statuses)],
                'is_featured' => (bool) rand(0, 1),
            ]);

            // Attach random media if any exist
            if (!empty($mediaIds)) {
                $selectedIds = Arr::random($mediaIds, min(rand(3, 4), count($mediaIds)));
                foreach ((array) $selectedIds as $index => $mediaId) {
                    ReviewMediaGallery::create([
                        'review_id'  => $review->id,
                        'media_id'   => $mediaId,
                        'sort_order' => $index,
                    ]);
                }
            }
        }
    }
}
