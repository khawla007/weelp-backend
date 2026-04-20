<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Tag;

class PublicTagController extends Controller
{
    public function getAllTags()
    {
        $tags = Tag::all();

        if ($tags->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No tags found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $tags], 200);
    }
}
