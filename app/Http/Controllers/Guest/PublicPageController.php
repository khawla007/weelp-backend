<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\SeoPayload;
use Illuminate\Http\JsonResponse;

class PublicPageController extends Controller
{
    private const HERO_STYLE_FIELDS = [
        'hero_overlay_color',
        'hero_overlay_opacity',
        'hero_content_vertical_position',
        'hero_heading_size',
        'hero_heading_color',
        'hero_heading_align',
        'hero_heading_bold',
        'hero_heading_italic',
        'hero_heading_underline',
        'hero_text_size',
        'hero_text_color',
        'hero_text_align',
        'hero_text_bold',
        'hero_text_italic',
        'hero_text_underline',
        'hero_button_radius',
        'hero_button_border_width',
        'hero_button_padding',
        'hero_button_margin',
        'hero_button_text_color',
        'hero_button_bg_color',
        'hero_button_border_color',
        'hero_button_text_size',
        'hero_button_align',
    ];

    public function show(string $slug): JsonResponse
    {
        $page = Page::published()
            ->where('slug', $slug)
            ->firstOrFail();

        $payload = [
            'id' => $page->id,
            'title' => $page->title,
            'slug' => $page->slug,
            'content' => $page->content,
            'excerpt' => $page->excerpt,
            'status' => $page->status,
            'published_at' => $page->published_at,
            'hero_background_image_url' => $page->hero_background_image_url,
            'hero_heading' => $page->hero_heading,
            'hero_text' => $page->hero_text,
            'hero_button_label' => $page->hero_button_label,
            'hero_button_url' => $page->hero_button_url,
            'seo' => SeoPayload::fromModel($page),
            'created_at' => $page->created_at,
            'updated_at' => $page->updated_at,
        ];

        foreach (self::HERO_STYLE_FIELDS as $field) {
            $payload[$field] = $page->{$field};
        }

        return response()->json(['data' => $payload]);
    }
}
