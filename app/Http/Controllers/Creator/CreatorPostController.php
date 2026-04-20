<?php

namespace App\Http\Controllers\Creator;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostItemTag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreatorPostController extends Controller
{
    public function index()
    {
        $posts = Post::with(['media', 'taggedItems.taggable.locations.city'])
            ->where('creator_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($posts);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'media_id' => 'nullable|exists:media,id',
            'caption' => 'required|string|max:2000',
            'tagged_items' => 'nullable|array',
            'tagged_items.*.taggable_id' => 'required_with:tagged_items|integer',
            'tagged_items.*.taggable_type' => 'required_with:tagged_items|string|in:App\\Models\\Activity,App\\Models\\Itinerary,App\\Models\\Package',
        ]);

        $post = Post::create([
            'creator_id' => Auth::id(),
            'media_id' => $validated['media_id'] ?? null,
            'caption' => $validated['caption'],
        ]);

        if (! empty($validated['tagged_items'])) {
            foreach ($validated['tagged_items'] as $item) {
                PostItemTag::create([
                    'post_id' => $post->id,
                    'taggable_id' => $item['taggable_id'],
                    'taggable_type' => $item['taggable_type'],
                ]);
            }
        }

        $post->load(['media', 'taggedItems.taggable.locations.city']);

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully.',
            'data' => $post,
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $post = Post::where('creator_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'media_id' => 'nullable|exists:media,id',
            'caption' => 'sometimes|required|string|max:2000',
            'status' => 'sometimes|in:draft,published,archived',
            'tagged_items' => 'nullable|array',
            'tagged_items.*.taggable_id' => 'required_with:tagged_items|integer',
            'tagged_items.*.taggable_type' => 'required_with:tagged_items|string|in:App\\Models\\Activity,App\\Models\\Itinerary,App\\Models\\Package',
        ]);

        $post->update(collect($validated)->only(['media_id', 'caption', 'status'])->filter()->toArray());

        if (array_key_exists('tagged_items', $validated)) {
            $post->taggedItems()->delete();

            if (! empty($validated['tagged_items'])) {
                foreach ($validated['tagged_items'] as $item) {
                    PostItemTag::create([
                        'post_id' => $post->id,
                        'taggable_id' => $item['taggable_id'],
                        'taggable_type' => $item['taggable_type'],
                    ]);
                }
            }
        }

        $post->load(['media', 'taggedItems.taggable.locations.city']);

        return response()->json([
            'success' => true,
            'message' => 'Post updated successfully.',
            'data' => $post,
        ]);
    }

    public function destroy($id)
    {
        $post = Post::where('creator_id', Auth::id())->findOrFail($id);
        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully.',
        ]);
    }
}
