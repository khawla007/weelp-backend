<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Tag;
use Illuminate\Http\Request;

class PublicBlogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('per_page', 4);
        $page = $request->get('page', 1);

        $search = $request->get('search'); // search name/slug/content
        $categorySlug = $request->get('category');
        $tagSlug = $request->get('tag');
        $sortBy = $request->get('sort_by', 'id_desc'); // Default: Newest First

        // Resolve category
        $category = $categorySlug
            ? Category::where('slug', $categorySlug)->first()
            : null;

        $categoryId = $category ? $category->id : null;

        // Resolve tag
        $tag = $tagSlug
            ? Tag::where('slug', $tagSlug)->first()
            : null;

        $tagId = $tag ? $tag->id : null;

        // ⭐ Main Query
        $query = Blog::query()
            ->with(['media', 'categories', 'tags'])

            // 🔍 SEARCH (same style)
            ->when($search, fn ($query) => $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%")
                    ->orWhere('content', 'like', "%{$search}%");
            })
            )

            // 🟡 CATEGORY FILTER
            ->when($categoryId, fn ($query) => $query->whereHas('categories', fn ($q) => $q->where('category_id', $categoryId)
            )
            )

            // 🔵 TAG FILTER
            ->when($tagId, fn ($query) => $query->whereHas('tags', fn ($q) => $q->where('tag_id', $tagId)
            )
            );

        // ⭐ SORTING — SAME STYLE
        switch ($sortBy) {

            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;

            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;

            case 'oldest':
            case 'id_asc':
                $query->orderBy('id', 'asc');
                break;

            case 'latest':
            case 'id_desc':
                $query->orderBy('id', 'desc');
                break;

            case 'published_first':
                $query->orderBy('publish', 'desc')->orderBy('id', 'desc');
                break;

            case 'draft_first':
                $query->orderBy('publish', 'asc')->orderBy('id', 'desc');
                break;

            default:
                $query->orderBy('id', 'desc');
                break;
        }

        // ⭐ Get all filtered blogs
        $allItems = $query->get();

        // ⭐ Manual pagination
        $paginatedItems = $allItems->forPage($page, $perPage);

        // ⭐ Transform response
        $transformed = $paginatedItems->map(function (Blog $blog, int $key) {

            return [
                'id' => $blog->id,
                'name' => $blog->name,
                'slug' => $blog->slug,
                'excerpt' => $blog->excerpt,
                'publish' => $blog->publish,

                'media_gallery' => $blog->media->map(function ($m) {
                    return [
                        'media_id' => $m->id,
                        'name' => $m->name ?? null,
                        'alt' => $m->alt_text ?? null,
                        'url' => $m->url ?? null,
                        'is_featured' => (int) ($m->pivot->is_featured ?? 0), // ← Include is_featured from pivot
                    ];
                }),

                'categories' => $blog->categories->map(function ($cat) {
                    return [
                        'category_id' => $cat->id,
                        'category_name' => $cat->name,
                        'slug' => $cat->slug ?? null,
                    ];
                }),

                'tags' => $blog->tags->map(function ($tag) {
                    return [
                        'tag_id' => $tag->id,
                        'tag_name' => $tag->name,
                        'slug' => $tag->slug ?? null,
                    ];
                }),

                'created_at' => $blog->created_at,
                'updated_at' => $blog->updated_at,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $transformed->values(),
            'current_page' => (int) $page,
            'per_page' => $perPage,
            'total' => $allItems->count(),
        ], 200);
    }

    // Get a single blog
    public function show($slug)
    {
        $blog = Blog::with([
            'media',
            'categories',
            'tags',
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        return response()->json([
            'id' => $blog->id,
            'name' => $blog->name,
            'slug' => $blog->slug,
            'content' => $blog->content,
            'excerpt' => $blog->excerpt,
            'publish' => $blog->publish,

            // ⭐ multiple media (gallery)
            'media_gallery' => $blog->media->map(function ($m) {
                return [
                    'media_id' => $m->id,
                    'name' => $m->name ?? null,
                    'alt' => $m->alt_text ?? null,
                    'url' => $m->url ?? null,
                    'is_featured' => (int) ($m->pivot->is_featured ?? 0), // ← Include is_featured from pivot
                ];
            }),

            // ⭐ multiple categories
            'categories' => $blog->categories->map(function ($cat) {
                return [
                    'id' => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug ?? null,
                ];
            }),

            // ⭐ multiple tags
            'tags' => $blog->tags->map(function ($tag) {
                return [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug ?? null,
                ];
            }),

            'created_at' => $blog->created_at,
            'updated_at' => $blog->updated_at,
        ], 200);
    }
}
