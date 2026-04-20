<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\Country;
use App\Models\Place;
use App\Models\State;

class PublicPlaceController extends Controller
{
    public function getPlacesByCity($country_slug, $state_slug, $city_slug)
    {
        $country = Country::where('slug', $country_slug)->first();
        if (! $country) {
            return response()->json(['success' => false, 'message' => 'Country not found'], 404);
        }

        $state = State::where('slug', $state_slug)->where('country_id', $country->id)->first();
        if (! $state) {
            return response()->json(['success' => false, 'message' => 'State not found'], 404);
        }

        $city = City::where('slug', $city_slug)->where('state_id', $state->id)->first();
        if (! $city) {
            return response()->json(['success' => false, 'message' => 'City not found'], 404);
        }

        $places = Place::with('mediaGallery.media')
            ->where('city_id', $city->id)
            ->get()
            ->map(function ($place) {
                // Get featured image from media_gallery
                $featuredImage = $place->mediaGallery->firstWhere('is_featured', true);
                $place->feature_image = $featuredImage?->media->url ?? null;
                unset($place->mediaGallery);

                return $place;
            });

        if (collect($places)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Place not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $places,
        ], 200);
    }
}
