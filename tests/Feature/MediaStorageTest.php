<?php

namespace Tests\Feature;

use App\Support\MediaStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class MediaStorageTest extends TestCase
{
    public function test_store_uploaded_file_returns_path_only_after_object_exists(): void
    {
        Storage::fake('minio');

        $file = UploadedFile::fake()->image('sample.jpg');

        $path = MediaStorage::storeUploadedFile($file, 'media', 'sample.jpg');

        $this->assertSame('media/sample.jpg', $path);
        $this->assertTrue(Storage::disk('minio')->exists($path));
    }

    public function test_put_object_returns_original_path_after_verified_write(): void
    {
        Storage::fake('minio');

        $path = MediaStorage::putObject('countries/sample.jpg', 'image-bytes');

        $this->assertSame('countries/sample.jpg', $path);
        $this->assertTrue(Storage::disk('minio')->exists($path));
    }

    public function test_put_object_throws_when_storage_write_fails(): void
    {
        Storage::shouldReceive('disk->put')->once()->with('media/missing.jpg', 'image-bytes')->andReturn(false);

        $this->expectException(RuntimeException::class);

        MediaStorage::putObject('media/missing.jpg', 'image-bytes');
    }
}
