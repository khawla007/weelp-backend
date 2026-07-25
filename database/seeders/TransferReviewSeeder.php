<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\Transfer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class TransferReviewSeeder extends Seeder
{
    public const REVIEW_TEXTS = [
        'The airport pickup was on time, the driver found us quickly, and the car was spotless.',
        'Our driver handled the luggage and made the ride to the hotel calm after a long flight.',
        'Clear pickup instructions, a comfortable vehicle, and no surprise charges at the end.',
        'The transfer was easy to book and the driver kept us updated before arriving.',
        'A smooth ride across Dubai with enough room for our family and all of our bags.',
    ];

    private const RATINGS = [5, 5, 5, 4, 5];

    public function run(): void
    {
        $customers = User::query()
            ->where('role', 'customer')
            ->orderBy('id')
            ->get();
        $transfers = Transfer::query()
            ->orderBy('id')
            ->get();

        if ($customers->isEmpty()) {
            $this->command?->warn('TransferReviewSeeder skipped: no customer users found.');

            return;
        }

        if ($transfers->isEmpty()) {
            $this->command?->warn('TransferReviewSeeder skipped: no transfers found.');

            return;
        }

        $baseDate = Carbon::parse('2026-07-20 10:00:00');
        $reviewCount = min($transfers->count(), count(self::REVIEW_TEXTS));

        for ($index = 0; $index < $reviewCount; $index++) {
            $transfer = $transfers[$index];
            $customer = $customers[$index % $customers->count()];
            $reviewText = self::REVIEW_TEXTS[$index];

            $review = Review::firstOrCreate(
                [
                    'user_id' => $customer->id,
                    'item_type' => 'transfer',
                    'item_id' => $transfer->id,
                    'review_text' => $reviewText,
                ],
                [
                    'order_id' => null,
                    'item_name_snapshot' => $transfer->name,
                    'item_slug_snapshot' => $transfer->slug,
                    'rating' => self::RATINGS[$index],
                    'status' => 'approved',
                    'is_featured' => $index < 3,
                ]
            );

            if (! $review->wasRecentlyCreated) {
                continue;
            }

            $timestamp = $baseDate->copy()->subDays($index);
            Review::query()
                ->whereKey($review->id)
                ->update([
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
        }
    }
}
