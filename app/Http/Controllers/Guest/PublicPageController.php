<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\SeoPayload;
use Illuminate\Http\JsonResponse;

class PublicPageController extends Controller
{
    public function show(string $slug): JsonResponse
    {
        $page = Page::published()
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'data' => [
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
            ],
        ]);
    }
}
