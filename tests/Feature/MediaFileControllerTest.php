<?php

namespace Tests\Feature;

use App\Models\Media;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MediaFileControllerTest extends TestCase
{
    use RefreshDatabase;

    private function seedMedia(int $width = 2400, int $height = 1800, string $ext = 'jpg'): Media
    {
        Storage::fake('minio');
        Storage::fake('public');

        $name = "source.{$ext}";
        $file = UploadedFile::fake()->image($name, $width, $height);
        Storage::disk('minio')->putFileAs('uploads', $file, $name);

        return Media::create([
            'name' => $name,
            'url' => "uploads/{$name}",
        ]);
    }

    public function test_resize_snaps_to_ladder_and_writes_cache(): void
    {
        $media = $this->seedMedia();

        $response = $this->get("/api/media/{$media->id}?w=400&q=80");

        $response->assertOk();
        $image = imagecreatefromstring($response->streamedContent());
        $this->assertNotFalse($image);
        $this->assertSame(480, imagesx($image));

        $this->assertTrue(Storage::disk('public')->exists("media/cache/{$media->id}/w480-q80.jpg"));
        $cacheControl = $response->headers->get('Cache-Control') ?? '';
        $this->assertStringContainsString('public', $cacheControl);
        $this->assertStringContainsString('max-age=31536000', $cacheControl);
        $this->assertStringContainsString('immutable', $cacheControl);
    }

    public function test_repeat_request_serves_identical_bytes_from_cache(): void
    {
        $media = $this->seedMedia();

        $first = $this->get("/api/media/{$media->id}?w=400&q=80")->streamedContent();
        $second = $this->get("/api/media/{$media->id}?w=400&q=80")->streamedContent();

        $this->assertSame(sha1($first), sha1($second));
        $this->assertNotEmpty($first);
    }

    public function test_width_above_max_clamps_to_2048(): void
    {
        $media = $this->seedMedia(width: 4000, height: 3000);

        $response = $this->get("/api/media/{$media->id}?w=99999");

        $response->assertOk();
        $image = imagecreatefromstring($response->streamedContent());
        $this->assertSame(2048, imagesx($image));
    }

    public function test_invalid_quality_returns_422(): void
    {
        $media = $this->seedMedia();

        $this->getJson("/api/media/{$media->id}?q=-1")->assertStatus(422);
    }

    public function test_width_below_minimum_returns_422(): void
    {
        $media = $this->seedMedia();

        $this->getJson("/api/media/{$media->id}?w=5")->assertStatus(422);
    }

    public function test_accept_image_webp_returns_webp_variant(): void
    {
        $media = $this->seedMedia();

        $response = $this
            ->withHeaders(['Accept' => 'image/webp,image/*;q=0.8'])
            ->get("/api/media/{$media->id}?w=400");

        $response->assertOk();
        $this->assertSame('image/webp', $response->headers->get('Content-Type'));
        $this->assertTrue(Storage::disk('public')->exists("media/cache/{$media->id}/w480-q75.webp"));
    }

    public function test_missing_media_returns_404(): void
    {
        Storage::fake('minio');
        Storage::fake('public');

        $this->get('/api/media/999999')->assertStatus(404);
    }

    public function test_missing_source_object_returns_404(): void
    {
        Storage::fake('minio');
        Storage::fake('public');

        $media = Media::create([
            'name' => 'gone.jpg',
            'url' => 'uploads/gone.jpg',
        ]);

        $this->get("/api/media/{$media->id}?w=400")->assertStatus(404);
    }

    public function test_no_query_params_streams_original_with_cache_headers(): void
    {
        $media = $this->seedMedia();

        $response = $this->get("/api/media/{$media->id}");

        $response->assertOk();
        $this->assertStringContainsString(
            'max-age=31536000',
            $response->headers->get('Cache-Control') ?? '',
        );
    }

    public function test_png_source_preserves_png_extension_in_cache(): void
    {
        $media = $this->seedMedia(width: 800, height: 600, ext: 'png');

        $response = $this->get("/api/media/{$media->id}?w=400");

        $response->assertOk();
        $this->assertTrue(Storage::disk('public')->exists("media/cache/{$media->id}/w480-q75.png"));
    }

    public function test_gif_source_bypasses_transform(): void
    {
        Storage::fake('minio');
        Storage::fake('public');

        $name = 'animated.gif';
        Storage::disk('minio')->put("uploads/{$name}", $this->minimalGif());
        $media = Media::create(['name' => $name, 'url' => "uploads/{$name}"]);

        $response = $this->get("/api/media/{$media->id}?w=400");

        $response->assertOk();
        $this->assertFalse(
            Storage::disk('public')->exists("media/cache/{$media->id}/w480-q75.gif"),
            'GIF must passthrough; no resized cache variant should be written.',
        );
    }

    private function minimalGif(): string
    {
        return base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
    }
}
