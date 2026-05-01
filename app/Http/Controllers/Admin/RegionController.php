<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Region;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Region::query()->with('countries');

        // Name search
        if ($request->has('name') && ! empty($request->name)) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        // Pagination
        $perPage = 10;
        $regions = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $request->input('page', 1));

        // Transform response
        $data = $regions->map(function ($region) {
            return [
                'id' => $region->id,
                'name' => $region->name,
                'slug' => $region->slug,
                'type' => $region->type,
                'description' => $region->description,
                'image_url' => $region->image_url,
                'countries_count' => $region->countries->count(),
                'countries' => $region->countries->map(function ($country) {
                    return [
                        'id' => $country->id,
                        'name' => $country->name,
                        'slug' => $country->slug,
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'total' => $regions->total(),
            'per_page' => $regions->perPage(),
            'last_page' => $regions->lastPage(),
            'current_page' => $regions->currentPage(),
        ]);
    }

    /**
     * List of regions dropdown
     */
    public function regionList()
    {
        try {
            $regions = Region::select('id', 'name', 'type')->orderBy('name')->get();

            if ($regions->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Region found',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Region list fetched successfully',
                'data' => $regions,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching region list',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            $region = Region::with('countries')->find($id);

            if (! $region) {
                return response()->json([
                    'success' => false,
                    'message' => 'Region not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'slug' => $region->slug,
                    'type' => $region->type,
                    'description' => $region->description,
                    'image_url' => $region->image_url,
                    'countries' => $region->countries->map(function ($country) {
                        return [
                            'id' => $country->id,
                            'name' => $country->name,
                            'slug' => $country->slug,
                        ];
                    }),
                    'country_ids' => $region->countries->pluck('id')->toArray(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching region',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'description' => 'nullable|string|max:5000',
                'image_url' => 'nullable|string|max:2048',
                'countries' => 'nullable|array',
                'countries.*' => 'exists:countries,id',
            ]);

            $region = Region::create([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            // Attach countries
            if (! empty($validated['countries'])) {
                $region->countries()->attach($validated['countries']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Region created successfully',
                'data' => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'slug' => $region->slug,
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error creating region',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $region = Region::find($id);

            if (! $region) {
                return response()->json([
                    'success' => false,
                    'message' => 'Region not found',
                ], 404);
            }

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|string|max:255',
                'description' => 'nullable|string|max:5000',
                'image_url' => 'nullable|string|max:2048',
                'countries' => 'nullable|array',
                'countries.*' => 'exists:countries,id',
            ]);

            $region->update([
                'name' => $validated['name'],
                'type' => $validated['type'],
                'description' => $validated['description'] ?? null,
                'image_url' => $validated['image_url'] ?? null,
            ]);

            // Sync countries
            if (isset($validated['countries'])) {
                $region->countries()->sync($validated['countries']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Region updated successfully',
                'data' => [
                    'id' => $region->id,
                    'name' => $region->name,
                    'slug' => $region->slug,
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error updating region',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $region = Region::find($id);

            if (! $region) {
                return response()->json([
                    'success' => false,
                    'message' => 'Region not found',
                ], 404);
            }

            // Detach countries
            $region->countries()->detach();

            // Delete region
            $region->delete();

            return response()->json([
                'success' => true,
                'message' => 'Region deleted successfully',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error deleting region',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
