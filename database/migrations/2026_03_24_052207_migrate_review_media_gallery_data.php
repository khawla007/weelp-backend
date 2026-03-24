<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Get all valid media IDs for validation
        $validMediaIds = DB::table('media')->pluck('id')->toArray();

        // Migrate JSON data to review_media_gallery rows
        $reviews = DB::table('reviews')
            ->whereNotNull('media_gallery')
            ->get(['id', 'media_gallery']);

        foreach ($reviews as $review) {
            $mediaIds = json_decode($review->media_gallery, true);

            if (!is_array($mediaIds) || empty($mediaIds)) {
                continue;
            }

            foreach ($mediaIds as $index => $mediaId) {
                // Skip invalid media IDs
                if (!in_array($mediaId, $validMediaIds)) {
                    continue;
                }

                DB::table('review_media_gallery')->insert([
                    'review_id' => $review->id,
                    'media_id' => $mediaId,
                    'sort_order' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        // Drop the old JSON column
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('media_gallery');
        });
    }

    public function down(): void
    {
        // Re-add the JSON column
        Schema::table('reviews', function (Blueprint $table) {
            $table->json('media_gallery')->nullable()->after('review_text');
        });

        // Migrate data back from review_media_gallery to JSON
        $reviewMedia = DB::table('review_media_gallery')
            ->orderBy('review_id')
            ->orderBy('sort_order')
            ->get();

        $grouped = $reviewMedia->groupBy('review_id');

        foreach ($grouped as $reviewId => $items) {
            DB::table('reviews')
                ->where('id', $reviewId)
                ->update(['media_gallery' => json_encode($items->pluck('media_id')->toArray())]);
        }
    }
};
