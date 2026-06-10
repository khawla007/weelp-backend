<?php

namespace App\Support;

final class RichTextContent
{
    public static function hasContent(?string $content): bool
    {
        $content = trim((string) $content);

        if ($content === '') {
            return false;
        }

        if (! str_starts_with($content, '{')) {
            return true;
        }

        $decoded = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            return true;
        }

        return self::nodeHasContent($decoded);
    }

    /**
     * @param  array<string, mixed>  $node
     */
    private static function nodeHasContent(array $node): bool
    {
        if (($node['type'] ?? null) === 'text' && trim((string) ($node['text'] ?? '')) !== '') {
            return true;
        }

        if (($node['type'] ?? null) === 'image' && trim((string) data_get($node, 'attrs.src', '')) !== '') {
            return true;
        }

        foreach (($node['content'] ?? []) as $child) {
            if (is_array($child) && self::nodeHasContent($child)) {
                return true;
            }
        }

        return false;
    }
}
