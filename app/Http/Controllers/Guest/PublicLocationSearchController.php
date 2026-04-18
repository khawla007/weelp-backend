<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Place;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicLocationSearchController extends Controller
{
    /**
     * Search cities + places for customer-facing pickup/destination pickers.
     *
     * Query params:
     *   - q: search term (optional, case-insensitive name LIKE %q%)
     *   - types: comma-separated list of 'city','place' (defaults to both)
     *   - limit: 1..20 (clamped)
     */
    public function search(Request $request): JsonResponse
    {
        $q     = $request->string('q')->toString();
        $types = array_filter(explode(',', $request->string('types')->toString()))
            ?: ['city', 'place'];
        $limit = (int) $request->input('limit', 20);
        $limit = max(1, min($limit, 20));

        $out = collect();

        if (in_array('city', $types, true)) {
            $cities = City::query()
                ->with(['state:id,name,country_id', 'state.country:id,name'])
                ->when($q, fn ($w) => $w->where('name', 'like', "%{$q}%"))
                ->limit($limit)
                ->get();

            foreach ($cities as $c) {
                $out->push([
                    'locatable_id'   => $c->id,
                    'locatable_type' => 'city',
                    'name'           => $c->name,
                    'type'           => 'city',
                    'city_name'      => $c->name,
                    'state_name'     => optional($c->state)->name,
                    'country_name'   => optional(optional($c->state)->country)->name,
                ]);
            }
        }

        if (in_array('place', $types, true)) {
            $places = Place::query()
                ->with(['city:id,name,state_id', 'city.state:id,name,country_id', 'city.state.country:id,name'])
                ->when($q, fn ($w) => $w->where('name', 'like', "%{$q}%"))
                ->limit($limit)
                ->get();

            foreach ($places as $p) {
                $out->push([
                    'locatable_id'   => $p->id,
                    'locatable_type' => 'place',
                    'name'           => $p->name,
                    'type'           => $p->type ?: 'place',
                    'city_name'      => optional($p->city)->name,
                    'state_name'     => optional(optional($p->city)->state)->name,
                    'country_name'   => optional(optional(optional($p->city)->state)->country)->name,
                ]);
            }
        }

        return response()->json([
            'success' => true,
            'data'    => $out->values(),
        ]);
    }
}
