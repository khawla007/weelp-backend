<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columnToLabel = [
            'facebook_url'         => 'Facebook',
            'facebook_url_profile' => 'Facebook',
            'instagram_url'        => 'Instagram',
            'instagram_handle'     => 'Instagram',
            'linkedin_url'         => 'LinkedIn',
            'youtube_url'          => 'YouTube',
            'myspace_url'          => 'MySpace',
            'pinterest_url'        => 'Pinterest',
        ];

        // Only process columns that actually exist in the database
        $existingColumns = Schema::getColumnListing('user_profiles');
        $columnToLabel   = array_filter(
            $columnToLabel,
            fn(string $col) => in_array($col, $existingColumns, true),
            ARRAY_FILTER_USE_KEY,
        );

        $profiles = DB::table('user_profiles')->get();

        foreach ($profiles as $profile) {
            foreach ($columnToLabel as $column => $label) {
                $value = $profile->$column ?? null;

                if (empty($value)) {
                    continue;
                }

                // For instagram_handle, prefix with Instagram URL if it's just a handle
                if ($column === 'instagram_handle' && ! str_starts_with($value, 'http')) {
                    $value = 'https://instagram.com/' . ltrim($value, '@');
                }

                // Skip if a row with the same label already exists for this profile
                $exists = DB::table('user_profile_urls')
                    ->where('user_profile_id', $profile->id)
                    ->where('label', $label)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('user_profile_urls')->insert([
                    'user_profile_id' => $profile->id,
                    'label'           => $label,
                    'url'             => $value,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Data migration — no automated rollback.
    }
};
