<?php

namespace App\Support;

use Illuminate\Support\Str;

class UploadRules
{
    public static function image(int $maxKb = 5120): array
    {
        // SVG explicitly excluded from mimetypes whitelist — embedded JS executes
        // when rendered inline.
        return [
            'file',
            'mimes:jpg,jpeg,png,webp',
            'mimetypes:image/jpeg,image/png,image/webp',
            'max:'.$maxKb,
        ];
    }

    public static function reviewAttachment(int $maxKb = 5120): array
    {
        return [
            'file',
            'mimes:jpg,jpeg,png,pdf',
            'mimetypes:image/jpeg,image/png,application/pdf',
            'max:'.$maxKb,
        ];
    }

    public static function mediaLibrary(int $maxKb = 51200): array
    {
        return [
            'file',
            'mimes:jpg,jpeg,png,webp,pdf,mp4',
            'mimetypes:image/jpeg,image/png,image/webp,application/pdf,video/mp4',
            'max:'.$maxKb,
        ];
    }

    public static function sanitizeName(?string $name, int $limit = 200): string
    {
        $name = (string) $name;
        $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name) ?? '';

        return Str::limit($name, $limit, '');
    }
}
