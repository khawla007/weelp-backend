<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->setFirstImageAsFeatured('itinerary_media_gallery', 'itinerary_id');
        $this->setFirstImageAsFeatured('package_media_gallery', 'package_id');
        $this->setFirstImageAsFeatured('transfer_media_gallery', 'transfer_id');
    }

    public function down(): void
    {
        DB::table('itinerary_media_gallery')->update(['is_featured' => false]);
        DB::table('package_media_gallery')->update(['is_featured' => false]);
        DB::table('transfer_media_gallery')->update(['is_featured' => false]);
    }

    /**
     * For each parent entity that has no featured image,
     * set is_featured = true on the first (oldest) media gallery entry.
     */
    private function setFirstImageAsFeatured(string $table, string $foreignKey): void
    {
        // Get parent IDs that already have a featured image
        $withFeatured = DB::table($table)
            ->where('is_featured', true)
            ->pluck($foreignKey)
            ->toArray();

        // Get the first (oldest) media gallery entry ID for each parent that lacks a featured image
        $firstEntryIds = DB::table($table)
            ->when(!empty($withFeatured), function ($query) use ($foreignKey, $withFeatured) {
                $query->whereNotIn($foreignKey, $withFeatured);
            })
            ->selectRaw("MIN(id) as id")
            ->groupBy($foreignKey)
            ->pluck('id')
            ->toArray();

        if (!empty($firstEntryIds)) {
            DB::table($table)
                ->whereIn('id', $firstEntryIds)
                ->update(['is_featured' => true]);
        }
    }
};
