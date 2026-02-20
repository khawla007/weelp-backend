<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr; 
use App\Models\Review;
use App\Models\User;
use App\Models\Media;

class ReviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $itemTypes = ['activity', 'itinerary', 'package', 'transfer'];
        $statuses = ['approved', 'pending'];

        $reviews = [];

        for ($i = 1; $i <= 11; $i++) {
            $reviews[] = [
                'user_id'       => rand(1, 5), 
                'item_type'     => $itemTypes[array_rand($itemTypes)],
                'item_id'       => rand(1, 10), 
                'rating'        => rand(1, 5),
                'review_text' => fake()->sentence(12),
                'media_gallery' => json_encode(
                    Arr::random([1, 2, 3, 4, 5], rand(1, 5)) 
                ),
                'status'        => $statuses[array_rand($statuses)],
                'created_at'    => now(),
                'updated_at'    => now(),
            ];
        }

        DB::table('reviews')->insert($reviews);
    }
}
