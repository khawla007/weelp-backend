<?php

namespace App\Http\Controllers\Guest;

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

    public function getMegaMenuData()
    {
        $mapCity = function ($city) {
            $featured = $city->mediaGallery->firstWhere('is_featured', true);
            $fallback = $city->mediaGallery->first();
            return [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'featured_image' => $featured?->media?->url ?? $fallback?->media?->url,
                'activities_count' => $city->activities_count,
                'places' => $city->places->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'slug' => $p->slug,
                ])->values(),
            ];
        };

        $regions = Region::with('countries:id')->get()->map(function ($region) use ($mapCity) {
            $countryIds = $region->countries->pluck('id');

            $cities = City::with(['mediaGallery.media', 'places:id,city_id,name,slug'])
                ->withCount('activities')
                ->whereHas('state', fn ($q) => $q->whereIn('country_id', $countryIds))
                ->orderByDesc('featured_destination')
                ->orderByDesc('activities_count')
                ->limit(10)
                ->get()
                ->map($mapCity);

            return [
                'id' => $region->id,
                'name' => $region->name,
                'slug' => $region->slug,
                'image_url' => $region->image_url,
                'cities' => $cities,
            ];
        });

        $trendingCities = City::with(['mediaGallery.media', 'places:id,city_id,name,slug'])
            ->withCount('activities')
            ->orderByDesc('featured_destination')
            ->orderByDesc('activities_count')
            ->limit(3)
            ->get()
            ->map($mapCity);

        return response()->json([
            'success' => true,
            'data' => $regions,
            'trending' => $trendingCities,
        ]);
    }
}
