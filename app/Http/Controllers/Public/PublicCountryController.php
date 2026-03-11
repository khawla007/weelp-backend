<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CountryMediaGallery;
use Illuminate\Http\Request;

class PublicCountryController extends Controller
{
    /**
     * Get all countries
     */
    public function index()
    {
        $countries = Country::with(['mediaGallery.media'])
            ->orderBy('name')
            ->get()
            ->map(function ($country) {
                // Get featured image from media_gallery
                $featuredImage = $country->mediaGallery->firstWhere('is_featured', true);
                return [
                    'id' => $country->id,
                    'name' => $country->name,
                    'slug' => $country->slug,
                    'code' => $country->code,
                    'description' => $country->description,
                    'feature_image' => $featuredImage?->media->url ?? null,
                    'featured_destination' => $country->featured_destination,
                    'media_gallery' => $country->mediaGallery->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'url' => $media->media->url ?? null,
                            'is_featured' => $media->is_featured ?? false,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    /**
     * Get featured countries for home page
     */
    public function getFeaturedCountries()
    {
        $countries = Country::with(['mediaGallery.media'])
            ->where('featured_destination', true)
            ->orderBy('name')
            ->get()
            ->map(function ($country) {
                // Get featured image from media_gallery
                $featuredImage = $country->mediaGallery->firstWhere('is_featured', true);
                return [
                    'id' => $country->id,
                    'name' => $country->name,
                    'slug' => $country->slug,
                    'description' => $country->description,
                    'feature_image' => $featuredImage?->media->url ?? null,
                    'states_count' => $country->states()->count(),
                ];
            });

        if ($countries->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No featured countries found'
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $countries
        ]);
    }

    /**
     * Get single country by slug
     */
    public function show($slug)
    {
        $country = Country::with([
            'mediaGallery.media',
            'locationDetails',
            'travelInfo',
            'seasons',
            'events',
            'faqs',
            'seo',
            'states' => function ($query) {
                $query->select('id', 'name', 'slug', 'country_id', 'feature_image');
            }
        ])
        ->where('slug', $slug)
        ->first();

        if (!$country) {
            return response()->json([
                'success' => false,
                'message' => 'Country not found'
            ], 404);
        }

        // Get featured image from media_gallery
        $featuredImage = $country->mediaGallery->firstWhere('is_featured', true);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $country->id,
                'name' => $country->name,
                'slug' => $country->slug,
                'code' => $country->code,
                'description' => $country->description,
                'feature_image' => $featuredImage?->media->url ?? null,
                'featured_destination' => $country->featured_destination,
                'media_gallery' => $country->mediaGallery->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'media_id' => $media->media_id,
                        'is_featured' => $media->is_featured ?? false,
                        'url' => $media->media->url ?? null,
                        'name' => $media->media->name ?? null,
                        'alt_text' => $media->media->alt_text ?? null,
                    ];
                }),
                'location_details' => $country->locationDetails,
                'travel_info' => $country->travelInfo,
                'seasons' => $country->seasons,
                'events' => $country->events,
                'faqs' => $country->faqs,
                'seo' => $country->seo,
                'states' => $country->states,
            ]
        ]);
    }
}
