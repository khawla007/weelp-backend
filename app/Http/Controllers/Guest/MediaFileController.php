<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Media;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Exceptions\DecoderException;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\Response;

class MediaFileController extends Controller
{
    private const MAX_WIDTH = 2048;

    private const MIN_WIDTH = 16;

    private const DEFAULT_QUALITY = 75;

    private const IMMUTABLE_MAX_AGE = 31536000;

    /**
     * Snap requested widths to a fixed ladder to bound the variant cache.
     * Without this, w∈[16,2048] × q∈[1,100] yields 400k+ files per media — a
     * cheap DoS via unique query strings. Ladder mirrors common DPR breakpoints.
     */
    private const WIDTH_LADDER = [320, 480, 640, 800, 960, 1280, 1600, 2048];

    public function show(Request $request, Media $media)
    {
        $path = $this->resolveSourcePath($media);
        if (! $path) {
            abort(404);
        }

        $data = $request->validate([
            'w' => 'sometimes|integer|min:'.self::MIN_WIDTH,
            'q' => 'sometimes|integer|min:1|max:100',
        ]);

        $width = isset($data['w']) ? $this->snapWidth((int) $data['w']) : null;
        $quality = isset($data['q']) ? (int) $data['q'] : self::DEFAULT_QUALITY;
        $wantsWebp = $this->prefersWebp($request);
        $sourceExt = strtolower(pathinfo($path, PATHINFO_EXTENSION)) ?: 'jpg';

        // GIF: GD flattens animation on encode. Always serve original.
        if ($sourceExt === 'gif') {
            return $this->passthrough($path);
        }

        // No transform requested → stream original with cache headers.
        if ($width === null && ! $wantsWebp && $quality === self::DEFAULT_QUALITY) {
            return $this->passthrough($path);
        }

        $outExt = $wantsWebp ? 'webp' : $sourceExt;
        $cacheKey = sprintf(
            'media/cache/%d/w%s-q%d.%s',
            $media->getKey(),
            $width ?? 'orig',
            $quality,
            $outExt,
        );

        // Cache hit: serve without touching MinIO.
        if (Storage::disk('public')->exists($cacheKey)) {
            return $this->immutable(Storage::disk('public')->response($cacheKey));
        }

        if (! Storage::disk('minio')->exists($path)) {
            abort(404);
        }

        try {
            $encoded = $this->transform(
                Storage::disk('minio')->get($path),
                $width,
                $quality,
                $outExt,
            );
        } catch (DecoderException) {
            abort(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        }

        Storage::disk('public')->put($cacheKey, $encoded);

        return $this->immutable(Storage::disk('public')->response($cacheKey));
    }

    private function passthrough(string $path): Response
    {
        if (! Storage::disk('minio')->exists($path)) {
            abort(404);
        }

        return $this->immutable(Storage::disk('minio')->response($path));
    }

    private function resolveSourcePath(Media $media): ?string
    {
        $path = $media->getRawOriginal('url');

        if (! $path) {
            return null;
        }

        if (str_starts_with($path, 'http')) {
            $path = ltrim(parse_url($path, PHP_URL_PATH) ?? '', '/');
            $bucket = config('filesystems.disks.minio.bucket');
            if ($bucket && str_starts_with($path, "{$bucket}/")) {
                $path = substr($path, strlen($bucket) + 1);
            }
        }

        return $path !== '' ? $path : null;
    }

    private function transform(string $sourceBytes, ?int $width, int $quality, string $outExt): string
    {
        $image = ImageManager::gd()->read($sourceBytes);

        if ($width !== null) {
            $image = $image->scaleDown(width: $width);
        }

        return (string) match ($outExt) {
            'webp' => $image->toWebp($quality),
            'png' => $image->toPng(),
            default => $image->toJpeg($quality),
        };
    }

    private function snapWidth(int $requested): int
    {
        foreach (self::WIDTH_LADDER as $rung) {
            if ($requested <= $rung) {
                return $rung;
            }
        }

        return self::MAX_WIDTH;
    }

    private function prefersWebp(Request $request): bool
    {
        return AcceptHeader::fromString($request->header('Accept', ''))->has('image/webp');
    }

    private function immutable(Response $response): Response
    {
        $response->setMaxAge(self::IMMUTABLE_MAX_AGE);
        $response->setPublic();
        $response->setImmutable();
        $response->setVary('Accept');

        return $response;
    }
}
