<?php

namespace App\Http\Controllers\Guest;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\State;

class PublicStateController extends Controller
{
    public function getStatesByCountry($country_slug)
    {
        $country = Country::where('slug', $country_slug)->first();

        if (! $country) {
            return response()->json(['success' => false, 'message' => 'Country not found'], 404);
        }

        $states = State::with('mediaGallery.media')
            ->where('country_id', $country->id)
            ->get()
            ->map(function ($state) {
                // Get featured image from media_gallery
                $featuredImage = $state->mediaGallery->firstWhere('is_featured', true);
                $state->feature_image = $featuredImage?->media->url ?? null;
                unset($state->mediaGallery);

                return $state;
            });

        if (collect($states)->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'States not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $states,
        ], 200);
    }
}
