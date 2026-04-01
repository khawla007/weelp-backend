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
        $posts = Post::with(['creator.avatarMedia', 'media', 'taggedItems.taggable'])
            ->where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json($posts);
    }

    public function show($id)
    {
        $post = Post::with(['creator.avatarMedia', 'media', 'taggedItems.taggable'])
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
