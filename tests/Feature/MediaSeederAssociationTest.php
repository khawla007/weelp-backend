<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityMediaGallery;
use App\Models\Media;
use Database\Seeders\MediaSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MediaSeederAssociationTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_seeder_preserves_existing_media_ids_and_associations(): void
    {
        $activity = Activity::create([
            'name' => 'Seed preservation activity',
            'slug' => 'seed-preservation-activity',
        ]);
        $media = Media::create([
            'name' => 'Existing curated image',
            'url' => 'countries/random-tourist-places/argentina/01-argentina-tourist-place-1-52a7e30197.jpg',
        ]);
        ActivityMediaGallery::create([
            'activity_id' => $activity->id,
            'media_id' => $media->id,
            'is_featured' => true,
        ]);

        $this->seed(MediaSeeder::class);
        $this->seed(MediaSeeder::class);

        $this->assertDatabaseHas('media', [
            'id' => $media->id,
            'url' => $media->getRawOriginal('url'),
        ]);
        $this->assertSame(1, Media::where('url', $media->getRawOriginal('url'))->count());
        $this->assertDatabaseHas('activity_media_gallery', [
            'activity_id' => $activity->id,
            'media_id' => $media->id,
            'is_featured' => true,
        ]);
        $this->assertSame(1, ActivityMediaGallery::where('activity_id', $activity->id)->count());
    }
}
