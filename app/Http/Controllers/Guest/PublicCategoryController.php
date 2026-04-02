<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Category;

class PublicCategoryController extends Controller
{
    public function getAllCategories()
    {
        $categories = Category::all();

        if ($categories->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No categories found.'], 404);
        }

        return response()->json(['success' => true, 'data' => $categories], 200);
    }
}
