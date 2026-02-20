<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;

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
