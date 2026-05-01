<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransferZone;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TransferZoneController extends Controller
{
    public function index(Request $request)
    {
        $query = TransferZone::query()
            ->withCount(['cities', 'places'])
            ->orderBy('sort_order')
            ->orderBy('id');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        $perPage = (int) $request->input('per_page', 25);
        $zones = $query->paginate($perPage);

        $zones->getCollection()->transform(function (TransferZone $zone) {
            $zone->locations_count = (int) $zone->cities_count + (int) $zone->places_count;
            return $zone;
        });

        return response()->json($zones);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:255',
            'slug'        => 'nullable|string|max:255|unique:transfer_zones,slug',
            'description' => 'nullable|string|max:5000',
            'sort_order'  => 'nullable|integer',
            'is_active'   => 'nullable|boolean',
        ]);

        $data['slug']       = $data['slug']       ?? Str::slug($data['name']);
        $data['sort_order'] = $data['sort_order'] ?? 0;
        $data['is_active']  = $data['is_active']  ?? true;

        $zone = TransferZone::create($data);

        return response()->json($zone, 201);
    }

    public function show($id)
    {
        $zone = TransferZone::withCount(['cities', 'places'])->findOrFail($id);
        $zone->locations_count = (int) $zone->cities_count + (int) $zone->places_count;

        return response()->json($zone);
    }

    public function update(Request $request, $id)
    {
        $zone = TransferZone::findOrFail($id);

        $data = $request->validate([
            'name'        => 'sometimes|required|string|max:255',
            'slug'        => ['sometimes', 'required', 'string', 'max:255', Rule::unique('transfer_zones', 'slug')->ignore($zone->id)],
            'description' => 'nullable|string|max:5000',
            'sort_order'  => 'nullable|integer',
            'is_active'   => 'nullable|boolean',
        ]);

        $zone->update($data);

        return response()->json($zone->fresh());
    }

    public function destroy($id)
    {
        $zone = TransferZone::findOrFail($id);
        $zone->delete();

        return response()->json(['deleted' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:transfer_zones,id',
        ]);

        $count = TransferZone::whereIn('id', $data['ids'])->delete();

        return response()->json(['deleted' => $count]);
    }
}
