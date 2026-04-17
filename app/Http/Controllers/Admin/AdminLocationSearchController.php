<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Place;
use Illuminate\Http\Request;

class AdminLocationSearchController extends Controller
{
    public function search(Request $request)
    {
        $q     = $request->string('q')->toString();
        $types = array_filter(explode(',', $request->string('types')->toString()))
            ?: ['city', 'place'];
        $limit = min((int) $request->input('limit', 20), 50);

        $out = collect();

        if (in_array('city', $types, true)) {
            $cities = City::query()
                ->with(['state:id,name,country_id', 'state.country:id,name'])
                ->when($q, fn ($w) => $w->where('name', 'like', "%{$q}%"))
                ->limit($limit)
                ->get();

            foreach ($cities as $c) {
                $out->push([
                    'id'             => $c->id,
                    'locatable_id'   => $c->id,
                    'locatable_type' => 'city',
                    'name'           => $c->name,
                    'type'           => 'city',
                    'city_name'      => $c->name,
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
                    'id'             => $p->id,
                    'locatable_id'   => $p->id,
                    'locatable_type' => 'place',
                    'name'           => $p->name,
                    'type'           => $p->type ?: 'place',
                    'city_name'      => optional($p->city)->name,
                    'country_name'   => optional(optional(optional($p->city)->state)->country)->name,
                ]);
            }
        }

        return response()->json([
            'data' => $out->values(),
        ]);
    }
}
