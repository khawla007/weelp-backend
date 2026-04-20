<?php

namespace Database\Seeders;

use App\Models\Blog;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UpdateBlogFeaturedSeeder extends Seeder
{
    public function run(): void
    {
        // Get all blogs that have media
        $blogs = Blog::with('media')->get();

        // Skip if no blogs
        if ($blogs->isEmpty()) {
            dump('⚠ No blogs found');

            return;
        }

        $updatedCount = 0;

        foreach ($blogs as $blog) {
            // Check if this blog already has a featured image
            $existingFeatured = DB::table('blog_media_gallery')
                ->where('blog_id', $blog->id)
                ->where('is_featured', true)
                ->first();

            // Skip if already has featured image
            if ($existingFeatured) {
                dump("Blog #{$blog->id} ({$blog->name}): Already has featured image");

                continue;
            }

            // Get the first media_id for this blog
            $firstMedia = DB::table('blog_media_gallery')
                ->where('blog_id', $blog->id)
                ->orderBy('id', 'asc') // First added image
                ->first();

            if ($firstMedia) {
                // Update first image as featured
                DB::table('blog_media_gallery')
                    ->where('id', $firstMedia->id)
                    ->update(['is_featured' => true]);

                $updatedCount++;
                dump("✅ Blog #{$blog->id} ({$blog->name}): Set image #{$firstMedia->media_id} as featured");
            } else {
                dump("⚠ Blog #{$blog->id} ({$blog->name}): No media found");
            }
        }

        dump("🎉 Total updated: {$updatedCount} blogs");
    }
}
