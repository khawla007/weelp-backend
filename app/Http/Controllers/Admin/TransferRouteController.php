<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Place;
use App\Models\TransferRoute;
use App\Models\TransferZoneLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TransferRouteController extends Controller
{
    private const ALLOWED_ENDPOINT_TYPES = ['city', 'place'];

    public function dropdown()
    {
        $routes = TransferRoute::query()
            ->where('is_active', true)
            ->with(['origin:id,name', 'destination:id,name'])
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'origin_type', 'origin_id', 'destination_type', 'destination_id', 'from_zone_id', 'to_zone_id']);

        return response()->json(['data' => $routes]);
    }

    public function index(Request $request)
    {
        $query = TransferRoute::query()
            ->with(['origin', 'destination', 'fromZone:id,name,slug', 'toZone:id,name,slug'])
            ->orderByDesc('id');

        if ($search = $request->string('q')->toString()) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->boolean('status'));
        }

        if ($request->filled('popular')) {
            $query->where('is_popular', $request->boolean('popular'));
        }

        if ($fromZone = (int) $request->input('from_zone_id')) {
            $query->where('from_zone_id', $fromZone);
        }
        if ($toZone = (int) $request->input('to_zone_id')) {
            $query->where('to_zone_id', $toZone);
        }

        $perPage = (int) $request->input('per_page', 25);
        return response()->json($query->paginate($perPage));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
        $data['from_zone_id'] = $this->resolveZoneId($data['origin_type'], (int) $data['origin_id']);
        $data['to_zone_id']   = $this->resolveZoneId($data['destination_type'], (int) $data['destination_id']);

        $route = TransferRoute::create($data);

        return response()->json(
            $route->load(['origin', 'destination', 'fromZone', 'toZone']),
            201
        );
    }

    public function show($id)
    {
        $route = TransferRoute::with(['origin', 'destination', 'fromZone', 'toZone'])->findOrFail($id);
        return response()->json($route);
    }

    public function update(Request $request, $id)
    {
        $route = TransferRoute::findOrFail($id);
        $data  = $this->validated($request, $route->id);
        $data['from_zone_id'] = $this->resolveZoneId($data['origin_type'], (int) $data['origin_id']);
        $data['to_zone_id']   = $this->resolveZoneId($data['destination_type'], (int) $data['destination_id']);

        $route->update($data);

        return response()->json($route->fresh(['origin', 'destination', 'fromZone', 'toZone']));
    }

    public function destroy($id)
    {
        $route = TransferRoute::findOrFail($id);
        $route->delete();
        return response()->json(['deleted' => true]);
    }

    public function bulkDelete(Request $request)
    {
        $data = $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'integer|exists:transfer_routes,id',
        ]);
        $count = TransferRoute::whereIn('id', $data['ids'])->delete();
        return response()->json(['deleted' => $count]);
    }

    public function toggleStatus($id)
    {
        $route = TransferRoute::findOrFail($id);
        $route->update(['is_active' => ! $route->is_active]);
        return response()->json(['is_active' => $route->is_active]);
    }

    public function togglePopular($id)
    {
        $route = TransferRoute::findOrFail($id);
        $route->update(['is_popular' => ! $route->is_popular]);
        return response()->json(['is_popular' => $route->is_popular]);
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $rule = Rule::unique('transfer_routes', 'slug');
        if ($ignoreId) {
            $rule = $rule->ignore($ignoreId);
        }

        $data = $request->validate([
            'name'             => 'required|string|max:255',
            'slug'             => ['nullable', 'string', 'max:255', $rule],
            'origin_type'      => ['required', Rule::in(self::ALLOWED_ENDPOINT_TYPES)],
            'origin_id'        => 'required|integer',
            'destination_type' => ['required', Rule::in(self::ALLOWED_ENDPOINT_TYPES)],
            'destination_id'   => 'required|integer',
            'from_zone_id'     => 'nullable|integer|exists:transfer_zones,id',
            'to_zone_id'       => 'nullable|integer|exists:transfer_zones,id',
            'distance_km'      => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:0',
            'is_active'        => 'nullable|boolean',
            'is_popular'       => 'nullable|boolean',
        ]);

        $this->assertEndpointExists($data['origin_type'], (int) $data['origin_id']);
        $this->assertEndpointExists($data['destination_type'], (int) $data['destination_id']);

        return $data;
    }

    private function assertEndpointExists(string $type, int $id): void
    {
        $exists = match ($type) {
            'city'  => City::whereKey($id)->exists(),
            'place' => Place::whereKey($id)->exists(),
            default => false,
        };

        abort_unless($exists, 422, "Endpoint {$type}#{$id} not found.");
    }

    private function resolveZoneId(string $locatableType, int $locatableId): ?int
    {
        $zoneId = TransferZoneLocation::where('locatable_type', $locatableType)
            ->where('locatable_id', $locatableId)
            ->join('transfer_zones', 'transfer_zones.id', '=', 'transfer_zone_locations.transfer_zone_id')
            ->where('transfer_zones.is_active', true)
            ->orderBy('transfer_zones.sort_order')
            ->value('transfer_zone_locations.transfer_zone_id');

        return $zoneId ? (int) $zoneId : null;
    }
}
