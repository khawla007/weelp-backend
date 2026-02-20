<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PublicFilterController extends Controller
{
    public function filter(Request $request)
    {
        $citySlug = $request->query('city_slug');
        $regionSlug = $request->query('region_slug');
        $categorySlug = $request->query('category_slug');
        $tagSlug = $request->query('tag_slug');

        $query = YourModel::query();

        if ($citySlug) {
            $city = City::where('slug', $citySlug)->first();
            if (!$city) {
                return response()->json(['message' => 'City not found'], 404);
            }
            $query->where('city_id', $city->id);
        }

        if ($regionSlug) {
            $region = Region::where('slug', $regionSlug)->first();
            if (!$region) {
                return response()->json(['message' => 'Region not found'], 404);
            }

            $cityIds = $region->countries()->with('cities')->get()->pluck('cities.*.id')->flatten()->toArray();
            $query->whereIn('city_id', $cityIds);
        }

        if ($categorySlug) {
            $query->whereHas('category', function ($q) use ($categorySlug) {
                $q->where('slug', $categorySlug);
            });
        }

        if ($tagSlug) {
            $query->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('slug', $tagSlug);
            });
        }

        return response()->json($query->get());
    }

}
