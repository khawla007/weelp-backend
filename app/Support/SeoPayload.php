<?php

namespace App\Support;

final class SeoPayload
{
    /**
     * @param  array<string, mixed>  $seo
     * @return array<string, mixed>
     */
    public static function normalize(array $seo, bool $includeMissing = true): array
    {
        $schemaData = $seo['schema_data'] ?? null;

        if (is_string($schemaData) && $schemaData !== '') {
            $decoded = json_decode($schemaData, true);
            $schemaData = json_last_error() === JSON_ERROR_NONE ? $decoded : null;
        }

        $values = [
            'meta_title' => $seo['meta_title'] ?? '',
            'meta_description' => $seo['meta_description'] ?? null,
            'keywords' => $seo['keywords'] ?? null,
            'og_image_url' => $seo['og_image_url'] ?? null,
            'canonical_url' => $seo['canonical_url'] ?? null,
            'schema_type' => $seo['schema_type'] ?? null,
            'schema_data' => is_array($schemaData) ? $schemaData : null,
            'head_code' => $seo['head_code'] ?? null,
            'body_code' => $seo['body_code'] ?? null,
            'footer_code' => $seo['footer_code'] ?? null,
        ];

        if ($includeMissing) {
            return $values;
        }

        return array_intersect_key($values, $seo);
    }

    public static function saveRelation(object $relation, array $seo): void
    {
        $existing = $relation->first();

        if ($existing) {
            $existing->fill(self::normalize($seo, false));
            $existing->save();

            return;
        }

        $relation->create(self::normalize($seo));
    }

    /**
     * @return array<string, string>
     */
    public static function rules(string $prefix = 'seo'): array
    {
        return [
            $prefix => 'sometimes|nullable|array',
            "{$prefix}.meta_title" => 'sometimes|nullable|string|max:255',
            "{$prefix}.meta_description" => 'sometimes|nullable|string|max:500',
            "{$prefix}.keywords" => 'sometimes|nullable|string|max:500',
            "{$prefix}.og_image_url" => 'sometimes|nullable|string|max:2048',
            "{$prefix}.canonical_url" => 'sometimes|nullable|string|max:2048',
            "{$prefix}.schema_type" => 'sometimes|nullable|string|max:255',
            "{$prefix}.schema_data" => 'sometimes|nullable',
            "{$prefix}.head_code" => 'sometimes|nullable|string|max:20000',
            "{$prefix}.body_code" => 'sometimes|nullable|string|max:20000',
            "{$prefix}.footer_code" => 'sometimes|nullable|string|max:20000',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function fromModel(?object $seo): array
    {
        return [
            'meta_title' => $seo?->meta_title,
            'meta_description' => $seo?->meta_description,
            'keywords' => $seo?->keywords,
            'og_image_url' => $seo?->og_image_url,
            'canonical_url' => $seo?->canonical_url,
            'schema_type' => $seo?->schema_type,
            'schema_data' => is_array($seo?->schema_data) ? $seo->schema_data : null,
            'head_code' => $seo?->head_code,
            'body_code' => $seo?->body_code,
            'footer_code' => $seo?->footer_code,
        ];
    }
}
