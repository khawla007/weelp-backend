<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AttributeController extends Controller
{
    /**
     * Display a listing of the Attribute.
     */
    // public function index()
    // {
    //     // return response()->json(Attribute::all());
    //     $attributes = Attribute::all();
    //     return response()->json([
    //         'success' => true,
    //         'data' => $attributes
    //     ]);
    // }
    public function index(Request $request)
    {
        $perPage = 6;
        $page    = $request->get('page', 1);
    
        $attributes = Attribute::paginate($perPage, ['*'], 'page', $page);
    
        return response()->json([
            'success'      => true,
            'data'         => $attributes->items(),
            'current_page' => $attributes->currentPage(),
            'per_page'     => $attributes->perPage(),
            'total'        => $attributes->total(),
        ]);
    }  

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:attributes,name',
            'slug' => 'sometimes|required|string|max:255|unique:attributes,slug',
            // 'type' => 'required|in:single_select,multi_select,text,number,yes_no',
            'type' => 'nullable|string',
            'description' => 'nullable|string',
            'values' => 'nullable|array',
            'default_value' => 'nullable|string',
        ]);

        $slug = Str::slug($request->name, '-');
        $taxonomy = 'act_' . $slug;

        $attribute = Attribute::create([
            'name' => $request->name,
            // 'slug' => $slug,
            'slug' => $request->slug,
            'type' => $request->type,
            'description' => $request->description,
            // 'values' => $request->type === 'single_select' || $request->type === 'multi_select' ? json_encode($request->values) : null,
            'values' => in_array($request->type, ['single_select', 'multi_select']) && is_array($request->values)
                ? implode(',', $request->values)
                : null,
            // 'default_value' => in_array($request->type, ['single_select', 'multi_select']) ? $request->default_value : null,
            'default_value' => $request->default_value ?? null,
            'taxonomy' => $taxonomy,
        ]);

        return response()->json($attribute, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        return response()->json(Attribute::findOrFail($id));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $attribute = Attribute::findOrFail($id);

        $request->validate([
            'name' => 'required|unique:attributes,name,' . $id,
            'slug' => 'sometimes|required|string|max:255|unique:attributes,slug,' . $id,
            // 'type' => 'required|in:single_select,multi_select,text,number,yes_no',
            'type' => 'nullable|string',
            'description' => 'nullable|string',
            'values' => 'nullable|array',
            'default_value' => 'nullable|string',
        ]);

        $slug = Str::slug($request->name, '-');
        $taxonomy = 'act_' . $slug;

        $attribute->update([
            'name' => $request->name,
            // 'slug' => $slug,
            'slug' => $request->slug,
            'type' => $request->type,
            'description' => $request->description,
            // 'values' => $request->type === 'single_select' || $request->type === 'multi_select' ? json_encode($request->values) : null,
            'values' => in_array($request->type, ['single_select', 'multi_select']) && is_array($request->values)
                ? implode(',', $request->values)
                : null,
            // 'default_value' => in_array($request->type, ['single_select', 'multi_select', 'text', 'number']) ? $request->default_value : null,
            'default_value' => $request->default_value ?? null,
            'taxonomy' => $taxonomy,
        ]);

        return response()->json($attribute);
    }


    // public function getDurationValues()
    // {
    //     return $this->getValuesByName('Duration');
    // }

    // public function getDifficultyValues()
    // {
    //     return $this->getValuesByName('Difficulty Level');
    // }

    // public function getGroupSizeValues()
    // {
    //     return $this->getValuesByName('Group Size');
    // }

    // public function getAgeRestrictionValues()
    // {
    //     return $this->getValuesByName('Age Restriction');
    // }

    // private function getValuesByName($name)
    // {
    //     $attribute = Attribute::where('name', $name)->first();

    //     if (!$attribute) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Attribute not found'
    //         ], 404);
    //     }

    //     $values = explode(',', $attribute->values);

    //     return response()->json([
    //         'success'      => true,
    //         'data'         => $values,
    //         'name'         => $attribute->name,
    //         'default'      => $attribute->default_value,
    //         'current_page' => 1,
    //         'per_page'     => count($values),
    //         'total'        => count($values),
    //     ], 200);
    // }

    public function getValuesBySlug($slug)
    {
        $attribute = Attribute::where('slug', $slug)->first();

        if (!$attribute) {
            return response()->json([
                'success' => false,
                'message' => 'Attribute not found'
            ], 404);
        }

        $values = explode(',', $attribute->values);

        return response()->json([
            'success'      => true,
            'data'         => $values,
            'name'         => $attribute->name,
            'default'      => $attribute->default_value
        ], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Attribute::findOrFail($id)->delete();
        return response()->json(['message' => 'Attribute deleted successfully']);
    }
}
