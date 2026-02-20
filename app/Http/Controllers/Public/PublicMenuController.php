<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\City;
use Illuminate\Http\Request;

class PublicMenuController extends Controller
{
    public function getAllRegionsWithCities()
    {
        // Load all regions with countries -> states -> cities
        $regions = Region::with('countries.states.cities')->get();

        $menu = $regions->map(function ($region) {
            $cities = [];
            foreach ($region->countries as $country) {
                foreach ($country->states as $state) {
                    foreach ($state->cities as $city) {
                        $cities[] = [
                            'id' => $city->id,
                            'name' => $city->name,
                            'slug' => $city->slug,
                        ];
                    }
                }
            }

            return [
                'region' => $region->name,
                'cities' => $cities,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $menu
        ], 200);
    }
}
