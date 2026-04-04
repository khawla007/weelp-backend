<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostLike;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PublicPostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::with(['creator.avatarMedia', 'media', 'taggedItems.taggable.locations.city'])
            ->where('status', 'published');

        // Source filter
        if ($request->query('source') === 'mine') {
            $user = Auth::guard('api')->user();
            if ($user) {
                $query->where('creator_id', $user->id);
            } else {
                return response()->json(['data' => [], 'last_page' => 1, 'current_page' => 1]);
            }
        }

        // Search filter — caption + creator name
        $query->when($request->query('search'), fn($q) =>
            $q->where(function ($sub) use ($request) {
                $search = $request->query('search');
                $sub->where('caption', 'like', "%{$search}%")
                    ->orWhereHas('creator', fn($cq) =>
                        $cq->where('name', 'like', "%{$search}%")
                    );
            })
        );

        // Sort
        switch ($request->query('sort', 'latest')) {
            case 'oldest':
                $query->orderBy('created_at', 'asc');
                break;
            case 'top_rated':
                $query->orderBy('likes_count', 'desc')->orderBy('created_at', 'desc');
                break;
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }

        $perPage = (int) $request->query('per_page', 15);

        return response()->json($query->paginate($perPage));
    }

    public function show($id)
    {
        $post = Post::with(['creator.avatarMedia', 'media', 'taggedItems.taggable.locations.city'])
            ->where('status', 'published')
            ->findOrFail($id);

        return response()->json($post);
    }

    public function toggleLike($id)
    {
        $user = Auth::user();
        $post = Post::findOrFail($id);

        $existingLike = PostLike::where('post_id', $post->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $post->decrement('likes_count');

            return response()->json([
                'success' => true,
                'liked' => false,
                'likes_count' => $post->fresh()->likes_count,
            ]);
        }

        PostLike::create([
            'post_id' => $post->id,
            'user_id' => $user->id,
        ]);
        $post->increment('likes_count');

        return response()->json([
            'success' => true,
            'liked' => true,
            'likes_count' => $post->fresh()->likes_count,
        ]);
    }

    public function incrementShare($id)
    {
        $post = Post::findOrFail($id);
        $post->increment('shares_count');

        return response()->json([
            'success' => true,
            'shares_count' => $post->fresh()->shares_count,
        ]);
    }
}
