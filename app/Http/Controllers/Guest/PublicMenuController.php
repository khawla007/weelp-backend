<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\Country;
use App\Models\City;

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
            'data' => $menu,
        ], 200);
    }

    public function getMegaMenuData()
    {
        $mapCountry = function ($country) {
            $featured = $country->mediaGallery->firstWhere('is_featured', true);
            $fallback = $country->mediaGallery->first();

            $stateIds = $country->states->pluck('id');

            $cities = City::select('id', 'state_id', 'name', 'slug')
                ->whereIn('state_id', $stateIds)
                ->orderByDesc('featured_destination')
                ->orderBy('name')
                ->limit(20)
                ->get()
                ->map(fn ($c) => [
                    'id' => $c->id,
                    'name' => $c->name,
                    'slug' => $c->slug,
                ])
                ->values();

            return [
                'id' => $country->id,
                'name' => $country->name,
                'slug' => $country->slug,
                'featured_image' => $featured?->media?->url ?? $fallback?->media?->url,
                'cities_count' => $cities->count(),
                'cities' => $cities,
            ];
        };

        $regions = Region::with([
                'countries' => fn ($q) => $q->orderByDesc('featured_destination')->orderBy('name'),
                'countries.mediaGallery.media',
                'countries.states:id,country_id',
            ])
            ->get()
            ->map(function ($region) use ($mapCountry) {
                $countries = $region->countries->take(3)->values()->map($mapCountry);

                return [
                    'id' => $region->id,
                    'name' => $region->name,
                    'slug' => $region->slug,
                    'image_url' => $region->image_url,
                    'countries' => $countries,
                ];
            });

        $trendingCountries = Country::with(['mediaGallery.media', 'states:id,country_id'])
            ->orderByDesc('featured_destination')
            ->orderBy('name')
            ->limit(3)
            ->get()
            ->map($mapCountry);

        return response()->json([
            'success' => true,
            'data' => $regions,
            'trending' => $trendingCountries,
        ]);
    }
}
