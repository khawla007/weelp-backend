<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Blog;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class BlogController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'           => 'required|string|max:255',
            'content'        => 'required|string',
            'publish'        => 'required|boolean',
            // 'featured_image' => 'required|exists:media,id',

            'media_gallery'   => 'required|array',
            'media_gallery.*' => 'required|exists:media,id',

            // 'category_id'    => 'required|exists:categories,id',
            // 'tag_id'         => 'required|exists:tags,id',

            'categories'     => 'required|array',
            'categories.*'   => 'required|exists:categories,id',
    
            'tags'           => 'required|array',
            'tags.*'         => 'required|exists:tags,id',

            'excerpt'        => 'required|string',
            // 'activity_id'    => 'required|exists:activities,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }

        $blog = Blog::create([
            'name'          => $request->name,
            'slug'           => Str::slug($request->name),
            'content'        => $request->content,
            'publish'        => $request->publish,
            // 'featured_image' => $request->featured_image,
            // 'category_id'    => $request->category_id,
            // 'tag_id'         => $request->tag_id,
            'excerpt'        => $request->excerpt,
            // 'activity_id'    => $request->activity_id,
        ]);

        $blog->categories()->sync($request->categories);
        $blog->tags()->sync($request->tags);
        $blog->media()->sync($request->media_gallery);

        // return response()->json($blog, 201);
        return response()->json([
            'message' => 'Blog created successfully',
            'Blog' => $blog
        ], 201);
        
    }

    // Update an existing blog
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'name'            => 'sometimes|string|max:255',
            'slug'            => 'sometimes|string|max:255',
            'content'         => 'sometimes|string',
            'publish'         => 'sometimes|boolean',
            // 'featured_image'  => 'sometimes|exists:media,id',

            'media_gallery'   => 'required|array',
            'media_gallery.*' => 'required|exists:media,id',

            // 'category_id'     => 'sometimes|exists:categories,id',
            // 'tag_id'          => 'sometimes|exists:tags,id',

            'categories'     => 'sometimes|array',
            'categories.*'   => 'required|exists:categories,id',
    
            'tags'           => 'sometimes|array',
            'tags.*'         => 'required|exists:tags,id',

            'excerpt'         => 'sometimes|string',
            // 'activity_id'     => 'sometimes|exists:activities,id',
        ]);
    
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        }
    
        $blog = Blog::findOrFail($id);
    
        foreach ($request->all() as $key => $value) {
            if (in_array($key, $blog->getFillable())) {
                $blog->$key = $value;
            }
        }
    
        $blog->save();

        // 👇 Update categories if passed
        if ($request->has('categories')) {
            $blog->categories()->sync($request->categories);
        }

        // 👇 Update tags if passed
        if ($request->has('tags')) {
            $blog->tags()->sync($request->tags);
        }

        // ⭐ Update media only if sent
        if ($request->has('media_gallery')) {
            $blog->media()->sync($request->media_gallery);
        }
    
        return response()->json([
            'message' => 'Blog updated successfully',
            'blog'    => $blog,
        ], 200);
    }    

    // Get all blogs  

    public function index(Request $request)
    {
        $perPage        = 3; 
        $page           = $request->get('page', 1); 
        
        $search         = $request->get('search'); // search name/slug/content
        $categorySlug   = $request->get('category');
        $tagSlug        = $request->get('tag');
        $sortBy         = $request->get('sort_by', 'id_desc'); // Default: Newest First
        
    
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
            ->when($search, fn($query) =>
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('slug', 'like', "%{$search}%")
                      ->orWhere('excerpt', 'like', "%{$search}%")
                      ->orWhere('content', 'like', "%{$search}%");
                })
            )
    
    
            // 🟡 CATEGORY FILTER
            ->when($categoryId, fn($query) =>
                $query->whereHas('categories', fn($q) =>
                    $q->where('category_id', $categoryId)
                )
            )
    
    
            // 🔵 TAG FILTER
            ->when($tagId, fn($query) =>
                $query->whereHas('tags', fn($q) =>
                    $q->where('tag_id', $tagId)
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
        $transformed = $paginatedItems->map(function ($blog) {
    
            return [
                'id'        => $blog->id,
                'name'      => $blog->name,
                'slug'      => $blog->slug,
                'excerpt'   => $blog->excerpt,
                'publish'   => $blog->publish,
    
                'media_gallery' => $blog->media->map(function ($m) {
                    return [
                        'media_id' => $m->id,
                        'name'     => $m->name ?? null,
                        'alt'      => $m->alt_text ?? null,
                        'url'      => $m->url ?? null,
                    ];
                }),
    
                'categories' => $blog->categories->map(function ($cat) {
                    return [
                        'category_id'   => $cat->id,
                        'category_name' => $cat->name,
                        'slug'          => $cat->slug ?? null,
                    ];
                }),
    
                'tags' => $blog->tags->map(function ($tag) {
                    return [
                        'tag_id'   => $tag->id,
                        'tag_name' => $tag->name,
                        'slug'     => $tag->slug ?? null,
                    ];
                }),
    
                'created_at' => $blog->created_at,
                'updated_at' => $blog->updated_at,
            ];
        });
    
    
        return response()->json([
            'success'      => true,
            'data'         => $transformed->values(),
            'current_page' => (int)$page,
            'per_page'     => $perPage,
            'total'        => $allItems->count(),
        ], 200);
    }    

    // Get a single blog
    public function show($id)
    {
        $blog = Blog::with([
            'media',
            'categories',
            'tags',
            // 'activity.locations.city.region'
        ])->findOrFail($id);
    
        // Extract primary location
        // $primaryLocation = $blog->activity?->locations?->firstWhere('location_type', 'primary');
        // $city   = $primaryLocation?->city;
        // $region = $city?->region;
    
        return response()->json([
            'id'        => $blog->id,
            'name'     => $blog->name,
            'slug'      => $blog->slug,
            'content'   => $blog->content,
            'excerpt'   => $blog->excerpt,
            'publish'   => $blog->publish,
    
            // ⭐ multiple media (gallery)
            'media_gallery' => $blog->media->map(function ($m) {
                return [
                    'media_id'   => $m->id,
                    'name' => $m->name ?? null,
                    'alt'  => $m->alt_text ?? null,
                    'url'  => $m->url ?? null,
                ];
            }),
    
            // ⭐ multiple categories
            'categories' => $blog->categories->map(function ($cat) {
                return [
                    'id'   => $cat->id,
                    'name' => $cat->name,
                    'slug' => $cat->slug ?? null,
                ];
            }),
    
            // ⭐ multiple tags
            'tags' => $blog->tags->map(function ($tag) {
                return [
                    'id'   => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug ?? null,
                ];
            }),
    
            'created_at' => $blog->created_at,
            'updated_at' => $blog->updated_at,
        ], 200);
    }    


    // Delete a blog
    public function destroy($id)
    {
        $blog = Blog::findOrFail($id);
        $blog->delete();
        return response()->json(['message' => 'Blog deleted successfully'], 200);
    }

    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'blog_ids'   => 'required|array',
            'blog_ids.*' => 'integer',
        ]);

        DB::beginTransaction();

        try {
            // ✅ Get only existing blog IDs
            $existingIds = Blog::whereIn('id', $validated['blog_ids'])
                ->pluck('id')
                ->toArray();

            if (!empty($existingIds)) {
                Blog::whereIn('id', $existingIds)->delete();
            }

            DB::commit();

            return response()->json([
                'success'        => true,
                'message'        => 'Blogs deleted successfully.',
                'deleted_ids'    => $existingIds,
                'ignored_ids'    => array_values(array_diff($validated['blog_ids'], $existingIds)),
            ], 200);

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete blogs.',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
