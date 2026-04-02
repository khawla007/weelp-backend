<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->get('all')) {
            $tags = Tag::orderBy('id', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $tags,
            ]);
        }

        $perPage = 6;
        $page = $request->get('page', 1);

        $tags = Tag::orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $page);

        return response()->json([
            'success' => true,
            'data' => $tags->items(),
            'current_page' => $tags->currentPage(),
            'per_page' => $tags->perPage(),
            'total' => $tags->total(),
        ]);
    }

    public function getTagList()
    {
        $tags = Tag::all();

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:tags,slug',
            'description' => 'nullable|string',
            'status' => 'required|in:active,draft',
            'is_featured' => 'nullable|boolean',
        ]);

        // $validated['slug'] = str_replace(' ', '_', strtolower($validated['name']));
        // $validated['taxonomy'] = 'tag';
        // $validated['post_type'] = 'activity';

        $tag = Tag::create($validated);

        return response()->json($tag, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $tag = Tag::findOrFail($id);

        return response()->json($tag);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $tag = Tag::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|max:255|unique:tags,slug,'.$tag->id,
            'description' => 'nullable|string',
            'status' => 'sometimes|required|in:active,draft',
            'is_featured' => 'nullable|boolean',
        ]);

        // if (isset($validated['name'])) {
        //     $validated['slug'] = str_replace(' ', '_', strtolower($validated['name']));
        // }

        $tag->update($validated);

        return response()->json($tag);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $tag = Tag::findOrFail($id);
        $tag->delete();

        return response()->json(['message' => 'Tag deleted successfully']);
    }

    /**
     * Bulk delete multiple tags.
     */
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'tag_ids' => 'required|array',
            'tag_ids.*' => 'integer|exists:tags,id',
        ]);

        $tagIds = $request->tag_ids;

        $deletedCount = Tag::whereIn('id', $tagIds)->delete();

        return response()->json([
            'success' => true,
            'message' => "$deletedCount tag(s) deleted successfully",
        ]);
    }
}
