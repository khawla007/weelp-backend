<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\State;
use App\Models\Country;

class PublicCitiesController extends Controller
{
    // -------------------------getting city behalf of state-------------------------
    public function getCitiesByState($country_slug, $state_slug)
    {
        $country = Country::where('slug', $country_slug)->first();
        if (!$country) {
            return response()->json(['success' => false, 'message' => 'Country not found'], 404);
        }

        $state = State::where('slug', $state_slug)->where('country_id', $country->id)->first();
        if (!$state) {
            return response()->json(['success' => false, 'message' => 'State not found'], 404);
        }

        $cities = City::with('mediaGallery.media')
            ->where('state_id', $state->id)
            ->get()
            ->map(function ($city) {
                // Get featured image from media_gallery
                $featuredImage = $city->mediaGallery->firstWhere('is_featured', true);
                $city->feature_image = $featuredImage?->media->url ?? null;
                unset($city->mediaGallery);
                return $city;
            });

        if (empty($cities)) {
            return response()->json([
                'success' => false,
                'message' => 'Cities not found'
            ]);
        }
        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }

    // ---------------------------getting all featured city for home page-------------------------
    public function getFeaturedCities()
    {
        $cities = City::with([
            'state.country.regions',
            'mediaGallery.media'
        ])
        ->where('featured_destination', true)
        ->get()
        ->map(function ($city) {
            // Get featured image from media_gallery
            $featuredImage = $city->mediaGallery->firstWhere('is_featured', true);
            $featureImageUrl = $featuredImage?->media->url ?? null;
            return [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'description' => $city->description,
                'featured_image' => $featureImageUrl,
                'state' => [
                    'id' => $city->state->id ?? null,
                    'name' => $city->state->name ?? null,
                ],
                'country' => [
                    'id' => $city->state->country->id ?? null,
                    'name' => $city->state->country->name ?? null,
                ],
                'region' => $city->state->country->regions->map(function ($region) {
                    return [
                        'id' => $region->id,
                        'name' => $region->name,
                    ];
                })
            ];
        });

        if ($cities->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No featured cities found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $cities
        ]);
    }

    // ----------------------------get single city page by slug--------------------------------

    public function getCityDetails($slug)
    {
        $city = City::with([
            'mediaGallery.media',
            'state',
            'country',
            'region',
            'locationDetails',
            'travelInfo',
            'seasons',
            'events',
            'additionalInfo',
            'faqs',
            'seo'
        ])->where('slug', $slug)->first();

        if (!$city) {
            return response()->json([
                'success' => false,
                'message' => 'City not found'
            ], 404);
        }

        // Get featured image from media_gallery
        $featureImageUrl = null;
        if ($city->mediaGallery && $city->mediaGallery->count()) {
            $featuredImage = $city->mediaGallery->firstWhere('is_featured', true);
            $featureImageUrl = $featuredImage?->media->url ?? null;
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'description' => $city->description,
                'feature_image' => $featureImageUrl,
                'featured_destination' => $city->featured_destination,
                'state' => $city->state ? [
                    'id' => $city->state->id,
                    'name' => $city->state->name
                ] : null,
                'country' => $city->country ? [
                    'id' => $city->country->id,
                    'name' => $city->country->name
                ] : null,
                'region' => $city->region ? [
                    'id' => $city->region->id,
                    'name' => $city->region->name
                ] : null,
                'location_details' => $city->locationDetails,
                'travel_info' => $city->travelInfo,
                'seasons' => $city->seasons,
                'events' => $city->events,
                'additional_info' => $city->additionalInfo,
                'faqs' => $city->faqs,
                'seo' => $city->seo
            ]
        ], 200);
    }

}

