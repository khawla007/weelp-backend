<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = 6;
        $page    = $request->get('page', 1);
    
        $categories = Category::paginate($perPage, ['*'], 'page', $page);
    
        return response()->json([
            'success'      => true,
            'data'         => $categories->items(),
            'current_page' => $categories->currentPage(),
            'per_page'     => $categories->perPage(),
            'total'        => $categories->total(),
        ]);
    }  

    /**
     * Display a listing for all items
    */
    public function getCatList()
    {
        $categories = Category::all();

        return response()->json([
            'success' => true,
            'data'    => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'sometimes|required|string|max:255|unique:categories,slug',
            'description' => 'nullable|string',
            'parent_id'   => 'nullable|exists:categories,id',
        ]);

        // $validated['slug']     = str_replace(' ', '-', strtolower($validated['name']));
        // $validated['taxonomy'] = 'cat';
        // $validated['post_type'] = 'activity';

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $category = Category::findOrFail($id);
        return response()->json($category);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $category  = Category::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'slug'        => 'sometimes|required|string|max:255|unique:categories,slug,' . $category->id,
            'description' => 'nullable|string',
            'parent_id'   => 'nullable|exists:categories,id',
        ]);

        // if (isset($validated['name'])) {
        //     $validated['slug'] = str_replace(' ', '_', strtolower($validated['name']));
        // }

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}
