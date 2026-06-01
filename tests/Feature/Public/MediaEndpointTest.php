<?php

namespace Tests\Feature\Public;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_media_url_uses_backend_proxy_path(): void
    {
        $media = Media::create([
            'name' => 'Sample image',
            'alt_text' => 'Sample image',
            'url' => 'media/sample.jpg',
        ]);

        $this->assertSame("/api/media/{$media->id}", $media->url);
    }

    public function test_missing_bucket_object_returns_404_not_500(): void
    {
        Storage::fake('minio');

        $media = Media::create([
            'name' => 'Orphan image',
            'alt_text' => 'Orphan image',
            'url' => 'states/tokyo_5_1773044030.jpg', // row exists, bucket object does not
        ]);

        $this->getJson("/api/media/{$media->id}")->assertStatus(404);
    }

    public function test_existing_bucket_object_streams_200(): void
    {
        Storage::fake('minio');
        Storage::disk('minio')->put('states/present.jpg', 'fake-bytes');

        $media = Media::create([
            'name' => 'Present image',
            'alt_text' => 'Present image',
            'url' => 'states/present.jpg',
        ]);

        $this->get("/api/media/{$media->id}")->assertStatus(200);
    }
}
