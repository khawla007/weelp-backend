<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Place;
use App\Models\TransferZone;
use App\Models\TransferZoneLocation;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TransferZoneLocationController extends Controller
{
    private const ALLOWED_TYPES = ['city', 'place'];

    public function index(Request $request, $zoneId)
    {
        $zone = TransferZone::findOrFail($zoneId);

        $search  = $request->string('q')->toString();
        $type    = $request->string('type')->toString();          // city|place|<place.type>
        $filter  = $request->string('filter')->toString() ?: 'all'; // all|assigned|unassigned
        $perPage = (int) $request->input('per_page', 25);

        $includeCities = ! $type || $type === 'city';
        $includePlaces = ! $type || in_array($type, ['place', 'airport', 'station', 'hotel'], true);

        $cityRows = collect();
        $placeRows = collect();

        if ($includeCities) {
            $cityQ = City::query()
                ->with(['state:id,name,country_id', 'state.country:id,name'])
                ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"));
            $cityRows = $cityQ->get()->map(function (City $c) {
                return [
                    'id'            => $c->id,
                    'locatable_id'   => $c->id,
                    'locatable_type' => 'city',
                    'name'          => $c->name,
                    'type'          => 'city',
                    'city_name'     => $c->name,
                    'state_name'    => optional($c->state)->name,
                    'country_name'  => optional(optional($c->state)->country)->name,
                ];
            });
        }

        if ($includePlaces) {
            $placeQ = Place::query()
                ->with(['city:id,name,state_id', 'city.state:id,name,country_id', 'city.state.country:id,name'])
                ->when($search, fn ($q) => $q->where('name', 'like', "%{$search}%"))
                ->when(in_array($type, ['airport', 'station', 'hotel'], true),
                    fn ($q) => $q->where('type', $type));
            $placeRows = $placeQ->get()->map(function (Place $p) {
                return [
                    'id'            => $p->id,
                    'locatable_id'   => $p->id,
                    'locatable_type' => 'place',
                    'name'          => $p->name,
                    'type'          => $p->type ?: 'place',
                    'city_name'     => optional($p->city)->name,
                    'state_name'    => optional(optional($p->city)->state)->name,
                    'country_name'  => optional(optional(optional($p->city)->state)->country)->name,
                ];
            });
        }

        $merged = $cityRows->concat($placeRows)->values();

        // Build current-zone lookup (all zones for these locatables).
        $assignments = TransferZoneLocation::query()
            ->with('zone:id,name,slug')
            ->whereIn('locatable_type', self::ALLOWED_TYPES)
            ->get()
            ->groupBy(fn ($row) => $row->locatable_type.':'.$row->locatable_id);

        $currentZoneId = (int) $zone->id;
        $merged = $merged->map(function ($row) use ($assignments, $currentZoneId) {
            $key = $row['locatable_type'].':'.$row['locatable_id'];
            $zones = ($assignments->get($key) ?? collect())
                ->map(fn ($a) => [
                    'id'   => (int) $a->zone?->id,
                    'name' => (string) $a->zone?->name,
                    'slug' => (string) $a->zone?->slug,
                ])
                ->values();
            $row['current_zones']      = $zones->all();
            $row['assigned_to_current'] = $zones->contains(fn ($z) => $z['id'] === $currentZoneId);
            return $row;
        });

        if ($filter === 'assigned') {
            $merged = $merged->filter(fn ($r) => $r['assigned_to_current']);
        } elseif ($filter === 'unassigned') {
            $merged = $merged->filter(fn ($r) => ! $r['assigned_to_current']);
        }

        $merged = $merged->values();

        // Manual pagination.
        $page  = max(1, (int) $request->input('page', 1));
        $total = $merged->count();
        $items = $merged->slice(($page - 1) * $perPage, $perPage)->values();

        return response()->json([
            'data'         => $items,
            'current_page' => $page,
            'per_page'     => $perPage,
            'total'        => $total,
            'last_page'    => (int) ceil($total / max(1, $perPage)),
        ]);
    }

    public function assign(Request $request, $zoneId)
    {
        $zone = TransferZone::findOrFail($zoneId);

        $data = $request->validate([
            'locations'                    => 'required|array|min:1',
            'locations.*.locatable_type'   => ['required', Rule::in(self::ALLOWED_TYPES)],
            'locations.*.locatable_id'     => 'required|integer',
        ]);

        $created = 0;
        foreach ($data['locations'] as $loc) {
            if (! $this->locatableExists($loc['locatable_type'], (int) $loc['locatable_id'])) {
                continue;
            }
            $row = TransferZoneLocation::firstOrCreate([
                'transfer_zone_id' => $zone->id,
                'locatable_type'   => $loc['locatable_type'],
                'locatable_id'     => (int) $loc['locatable_id'],
            ]);
            if ($row->wasRecentlyCreated) {
                $created++;
            }
        }

        return response()->json(['assigned' => $created]);
    }

    public function unassign(Request $request, $zoneId)
    {
        $zone = TransferZone::findOrFail($zoneId);

        $data = $request->validate([
            'locations'                    => 'required|array|min:1',
            'locations.*.locatable_type'   => ['required', Rule::in(self::ALLOWED_TYPES)],
            'locations.*.locatable_id'     => 'required|integer',
        ]);

        $removed = 0;
        foreach ($data['locations'] as $loc) {
            $removed += TransferZoneLocation::query()
                ->where('transfer_zone_id', $zone->id)
                ->where('locatable_type', $loc['locatable_type'])
                ->where('locatable_id', (int) $loc['locatable_id'])
                ->delete();
        }

        return response()->json(['unassigned' => $removed]);
    }

    private function locatableExists(string $type, int $id): bool
    {
        return match ($type) {
            'city'  => City::whereKey($id)->exists(),
            'place' => Place::whereKey($id)->exists(),
            default => false,
        };
    }
}
