<?php

namespace Database\Seeders;

use App\Models\Blog;
use App\Models\Category;
use App\Models\Media;
use App\Models\Tag;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();

        // Get all ids
        $allCategoryIds = Category::pluck('id')->toArray();
        $allTagIds = Tag::pluck('id')->toArray();
        $allMediaIds = Media::pluck('id')->toArray();

        // Safety check
        if (empty($allCategoryIds) || empty($allTagIds) || empty($allMediaIds)) {
            dump('⚠ Please seed categories / tags / media first');

            return;
        }

        // Create 10 blogs
        for ($i = 1; $i <= 10; $i++) {

            $title = $faker->sentence(4);

            $blog = Blog::create([
                'name' => $title,
                'slug' => Str::slug($title).'-'.$i, // prevent duplicate slug
                'content' => $faker->paragraph(8),
                'publish' => $faker->boolean(80), // 80% published
                'excerpt' => $faker->sentence(12),
            ]);

            // ⭐ Random Categories (1–3)
            $blog->categories()->sync(
                $faker->randomElements($allCategoryIds, rand(1, 3))
            );

            // ⭐ Random Tags (1–4)
            $blog->tags()->sync(
                $faker->randomElements($allTagIds, rand(1, 4))
            );

            // ⭐ Random Media (1–5)
            $selectedMediaIds = $faker->randomElements($allMediaIds, rand(1, 5));

            // Attach media with pivot data, randomly selecting one as featured
            $mediaToAttach = [];
            $featuredIndex = array_rand($selectedMediaIds); // Random index for featured

            foreach ($selectedMediaIds as $index => $mediaId) {
                $mediaToAttach[$mediaId] = [
                    'is_featured' => $index === $featuredIndex, // Only one image is featured
                ];
            }

            $blog->media()->sync($mediaToAttach);
        }
    }
}
