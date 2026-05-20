<?php

namespace Tests\Feature\Public;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
