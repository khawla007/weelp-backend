<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\CountryAdditionalInfo;
use App\Models\CountryEvent;
use App\Models\CountryFaq;
use App\Models\CountryLocationDetail;
use App\Models\CountryMediaGallery;
use App\Models\CountrySeason;
use App\Models\CountrySeo;
use App\Models\CountryTravelInfo;
use Illuminate\Http\Request;

class CountryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     return response()->json(Country::all());
    // }

    public function index(Request $request)
    {
        $query = Country::query()->with('mediaGallery.media', 'regions');

        // 🔍 Name search
        if ($request->has('name') && ! empty($request->name)) {
            $query->where('name', 'like', '%'.$request->name.'%');
        }

        // 📄 Pagination (perPage fix)
        $perPage = 4;
        $countries = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $request->input('page', 1));

        // 🎯 Transform response
        $data = $countries->map(function (Country $country, int $key) {
            // Get featured image from media_gallery
            $featuredImage = $country->mediaGallery->firstWhere('is_featured', true);

            return [
                'id' => $country->id,
                'name' => $country->name,
                'code' => $country->code,
                'slug' => $country->slug,
                'type' => $country->type,
                'region' => $country->region, // Legacy field for backward compatibility
                'regions' => $country->regions->map(function ($region) {
                    return [
                        'id' => $region->id,
                        'name' => $region->name,
                        'slug' => $region->slug,
                    ];
                }),
                'description' => $country->description,
                'feature_image' => $featuredImage?->media->url ?? null, // Featured from media_gallery
                'featured_destination' => $country->featured_destination,
                // ✅ Custom Media format
                'media_gallery' => $country->mediaGallery->map(function ($gallery) {
                    return [
                        'id' => $gallery->id,
                        'country_id' => $gallery->country_id,
                        'media_id' => $gallery->media_id,
                        'is_featured' => $gallery->is_featured ?? false,
                        'name' => $gallery->media->name ?? null,
                        'alt_text' => $gallery->media->alt_text ?? null,
                        'url' => $gallery->media->url ?? null,
                    ];
                }),
            ];
        });

        // 🎯 Custom response format
        return response()->json([
            'success' => true,
            'data' => $data,
            'total' => $countries->total(),
            'per_page' => $countries->perPage(),
            'last_page' => $countries->lastPage(),
            'current_page' => $countries->currentPage(),
        ]);
    }

    /**
     * List of counntry dropdown
     */
    public function countryList()
    {
        try {
            $countries = Country::select('id', 'name', 'type')->orderBy('name')->get();

            if ($countries->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No countries found',
                    'data' => [],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Country list fetched successfully',
                'data' => $countries,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching country list',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // Country fields
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'slug' => 'required|string|max:255',
            // 'type' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'featured_destination' => 'boolean',

            // Media (array of objects)
            'media_gallery' => 'nullable|array',

            // Location Details
            'location_details.latitude' => 'nullable|string|max:32',
            'location_details.longitude' => 'nullable|string|max:32',
            'location_details.capital_city' => 'nullable|string|max:120',
            'location_details.population' => 'nullable|integer',
            'location_details.currency' => 'nullable|string|max:8',
            'location_details.timezone' => 'nullable|string|max:64',
            'location_details.language' => 'nullable|array',
            'location_details.local_cuisine' => 'nullable|array',

            // Travel Info
            'travel_info.airport' => 'nullable|string|max:255',
            'travel_info.public_transportation' => 'nullable|array',
            'travel_info.taxi_available' => 'boolean',
            'travel_info.rental_cars_available' => 'boolean',
            'travel_info.hotels' => 'boolean',
            'travel_info.hostels' => 'boolean',
            'travel_info.apartments' => 'boolean',
            'travel_info.resorts' => 'boolean',
            'travel_info.visa_requirements' => 'nullable|string|max:5000',
            'travel_info.best_time_to_visit' => 'nullable|string|max:5000',
            'travel_info.travel_tips' => 'nullable|string|max:5000',
            'travel_info.safety_information' => 'nullable|string|max:5000',

            // Season (array of objects)
            'seasons' => 'nullable|array',
            'seasons.*.name' => 'nullable|string|max:120',
            'seasons.*.months' => 'nullable|array',
            'seasons.*.weather' => 'nullable|string|max:5000',
            'seasons.*.activities' => 'nullable|array',

            // Event (array of objects)
            'events' => 'nullable|array',
            'events.*.name' => 'nullable|string|max:120',
            'events.*.type' => 'nullable|array',
            'events.*.date' => 'nullable|date',
            'events.*.location' => 'nullable|string|max:255',
            'events.*.description' => 'nullable|string|max:5000',

            // Additional Info
            'additional_info' => 'nullable|array',
            'additional_info.*.title' => 'required|string|max:200',
            'additional_info.*.content' => 'required|string|max:5000',

            // FAQs
            'faqs' => 'array',
            'faqs.*.question' => 'required|string|max:200',
            'faqs.*.answer' => 'required|string|max:5000',

            // SEO
            'seo.meta_title' => 'nullable|string|max:200',
            'seo.meta_description' => 'nullable|string|max:500',
            'seo.keywords' => 'nullable|string|max:500',
            'seo.og_image_url' => 'nullable|url',
            'seo.canonical_url' => 'nullable|url',
            'seo.schema_type' => 'nullable|string|max:50',
            'seo.schema_data' => 'nullable|array',
        ]);

        $exists = Country::where('name', $request->name)
            ->orWhere('slug', $request->slug)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This country already exists, please choose another name.',
            ], 422); // 422 = Unprocessable Entity (validation error)
        }
        // Create Country
        $country = Country::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'slug' => $validated['slug'],
            // 'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'featured_destination' => $validated['featured_destination'] ?? false,
        ]);

        // Media Details
        if (! empty($validated['media_gallery'])) {
            // Ensure only one featured image
            $hasFeatured = false;
            foreach ($validated['media_gallery'] as $media) {
                $isFeatured = $media['is_featured'] ?? false;
                if ($isFeatured) {
                    if ($hasFeatured) {
                        // Already has featured, skip this one
                        $isFeatured = false;
                    } else {
                        $hasFeatured = true;
                    }
                }

                CountryMediaGallery::create([
                    'country_id' => $country->id,
                    'media_id' => $media['media_id'],
                    'is_featured' => $isFeatured,
                ]);
            }
        }

        // Location Details
        if (! empty($validated['location_details'])) {
            $validated['location_details']['country_id'] = $country->id;
            CountryLocationDetail::create($validated['location_details']);
        }

        // Travel Info
        if (! empty($validated['travel_info'])) {
            $validated['travel_info']['country_id'] = $country->id;
            CountryTravelInfo::create($validated['travel_info']);
        }

        // Season
        if ($request->has('seasons')) {
            foreach ($request->seasons as $season) {
                if (empty($season['name'])) {
                    continue;
                }
                $country->seasons()->create($season);
            }
        }

        // Event
        if ($request->has('events')) {
            foreach ($request->events as $event) {
                if (empty($event['name'])) {
                    continue;
                }
                $country->events()->create($event);
            }
        }

        // Additional Info
        if (! empty($validated['additional_info'])) {
            foreach ($validated['additional_info'] as $additional) {
                if (empty($additional['title'])) {
                    continue;
                }
                $additional['country_id'] = $country->id;
                CountryAdditionalInfo::create($additional);
            }
        }

        // FAQs
        if (! empty($validated['faqs'])) {
            $questionNumber = 1;
            foreach ($validated['faqs'] as $faq) {
                if (empty($faq['question'])) {
                    continue;
                }
                CountryFaq::create([
                    'country_id' => $country->id,
                    'question_number' => $questionNumber++,
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                ]);
            }
        }

        // SEO
        if (! empty($validated['seo'])) {
            $validated['seo']['country_id'] = $country->id;
            CountrySeo::create($validated['seo']);
        }

        return response()->json([
            'message' => 'Country created successfully',
            'country' => $country,
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $country = Country::with([
            'mediaGallery.media',
            'locationDetails',
            'travelInfo',
            'seasons',
            'events',
            'additionalInfo',
            'faqs',
            'seo',
        ])->find($id);

        // Check if country exists FIRST (before accessing properties)
        if (! $country) {
            return response()->json(['message' => 'Country not found'], 404);
        }

        // media_gallery ko transform karna
        if ($country->mediaGallery->count()) {
            $mediaCollection = $country->mediaGallery->map(function ($gallery) {
                return [
                    'id' => $gallery->id,
                    'country_id' => $gallery->country_id,
                    'media_id' => $gallery->media_id,
                    'is_featured' => $gallery->is_featured ?? false,
                    'name' => $gallery->media->name ?? null,
                    'alt_text' => $gallery->media->alt_text ?? null,
                    'url' => $gallery->media->url ?? null,
                ];
            })->values();
            // Get featured image from media_gallery
            $featuredImage = $mediaCollection->firstWhere('is_featured', true);
            $country->feature_image = $featuredImage['url'] ?? null;
            $country->media_gallery = $mediaCollection->toArray();
            unset($country->mediaGallery); // nested relation hatane ke liye
        } else {
            $country->media_gallery = [];
            $country->feature_image = null;
        }

        return response()->json($country);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $country = Country::findOrFail($id);

        $validated = $request->validate([
            // Country fields
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:10',
            'slug' => 'nullable|string|max:255',
            // 'type' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:5000',
            'featured_destination' => 'boolean',

            // Media (array of objects)
            'media_gallery' => 'nullable|array',

            // Location Details
            'location_details.latitude' => 'nullable|string|max:32',
            'location_details.longitude' => 'nullable|string|max:32',
            'location_details.capital_city' => 'nullable|string|max:120',
            'location_details.population' => 'nullable|integer',
            'location_details.currency' => 'nullable|string|max:8',
            'location_details.timezone' => 'nullable|string|max:64',
            'location_details.language' => 'nullable|array',
            'location_details.local_cuisine' => 'nullable|array',

            // Travel Info
            'travel_info.airport' => 'nullable|string|max:255',
            'travel_info.public_transportation' => 'nullable|array',
            'travel_info.taxi_available' => 'boolean',
            'travel_info.rental_cars_available' => 'boolean',
            'travel_info.hotels' => 'boolean',
            'travel_info.hostels' => 'boolean',
            'travel_info.apartments' => 'boolean',
            'travel_info.resorts' => 'boolean',
            'travel_info.visa_requirements' => 'nullable|string|max:5000',
            'travel_info.best_time_to_visit' => 'nullable|string|max:5000',
            'travel_info.travel_tips' => 'nullable|string|max:5000',
            'travel_info.safety_information' => 'nullable|string|max:5000',

            // Season (array of objects)
            'seasons' => 'nullable|array',
            'seasons.*.id' => 'nullable|integer|exists:country_seasons,id',
            'seasons.*.name' => 'nullable|string|max:120',
            'seasons.*.months' => 'nullable|array',
            'seasons.*.weather' => 'nullable|string|max:5000',
            'seasons.*.activities' => 'nullable|array',

            // Event (array of objects)
            'events' => 'nullable|array',
            'events.*.id' => 'nullable|integer|exists:country_events,id',
            'events.*.name' => 'nullable|string|max:120',
            'events.*.type' => 'nullable|array',
            'events.*.date' => 'nullable|date',
            'events.*.location' => 'nullable|string|max:255',
            'events.*.description' => 'nullable|string|max:5000',

            // Additional Info
            'additional_info' => 'nullable|array',
            'additional_info.*.id' => 'nullable|integer|exists:country_additional_infos,id',
            'additional_info.*.title' => 'required|string|max:200',
            'additional_info.*.content' => 'required|string|max:5000',

            // FAQs
            'faqs' => 'nullable|array',
            'faqs.*.id' => 'nullable|integer|exists:country_faqs,id',
            'faqs.*.question' => 'required|string|max:200',
            'faqs.*.answer' => 'required|string|max:5000',

            // SEO
            'seo.meta_title' => 'nullable|string|max:200',
            'seo.meta_description' => 'nullable|string|max:500',
            'seo.keywords' => 'nullable|string|max:500',
            'seo.og_image_url' => 'nullable|url',
            'seo.canonical_url' => 'nullable|url',
            'seo.schema_type' => 'nullable|string|max:50',
            'seo.schema_data' => 'nullable|array',
        ]);

        // === Country main fields update ===
        $country->update([
            'name' => $validated['name'] ?? $country->name,
            'code' => $validated['code'] ?? $country->code,
            'slug' => $validated['slug'] ?? $country->slug,
            // 'type' => $validated['type'] ?? $country->type,
            'description' => $validated['description'] ?? $country->description,
            'featured_destination' => $validated['featured_destination'] ?? $country->featured_destination,
        ]);

        // === Media (delete old & insert new) ===
        if (isset($validated['media_gallery'])) {
            CountryMediaGallery::where('country_id', $country->id)->delete();

            // Ensure only one featured image
            $hasFeatured = false;
            foreach ($validated['media_gallery'] as $media) {
                $isFeatured = $media['is_featured'] ?? false;
                if ($isFeatured) {
                    if ($hasFeatured) {
                        // Already has featured, skip this one
                        $isFeatured = false;
                    } else {
                        $hasFeatured = true;
                    }
                }

                CountryMediaGallery::create([
                    'country_id' => $country->id,
                    'media_id' => $media['media_id'],
                    'is_featured' => $isFeatured,
                ]);
            }
        }

        // === Location Details (hasOne) ===
        if (! empty($validated['location_details'])) {
            if ($country->locationDetails) {
                $country->locationDetails->update($validated['location_details']);
            } else {
                $country->locationDetails()->create($validated['location_details']);
            }
        }

        // === Travel Info (hasOne) ===
        if (! empty($validated['travel_info'])) {
            if ($country->travelInfo) {
                $country->travelInfo->update($validated['travel_info']);
            } else {
                $country->travelInfo()->create($validated['travel_info']);
            }
        }

        // === Seasons (hasMany) ===
        if ($request->has('seasons')) {
            $sentIds = collect($request->seasons)->pluck('id')->filter()->toArray();

            // delete missing
            CountrySeason::where('country_id', $country->id)
                ->whereNotIn('id', $sentIds)
                ->delete();

            foreach ($request->seasons as $season) {
                if (empty($season['name'])) {
                    continue;
                }
                if (! empty($season['id'])) {
                    CountrySeason::where('id', $season['id'])->update($season);
                } else {
                    $country->seasons()->create($season);
                }
            }
        }

        // === Events (hasMany) ===
        if ($request->has('events')) {
            $sentIds = collect($request->events)->pluck('id')->filter()->toArray();

            CountryEvent::where('country_id', $country->id)
                ->whereNotIn('id', $sentIds)
                ->delete();

            foreach ($request->events as $event) {
                if (empty($event['name'])) {
                    continue;
                }
                if (! empty($event['id'])) {
                    CountryEvent::where('id', $event['id'])->update($event);
                } else {
                    $country->events()->create($event);
                }
            }
        }

        // === Additional Info (hasMany) ===
        if ($request->has('additional_info')) {
            $sentIds = collect($request->additional_info)->pluck('id')->filter()->toArray();

            CountryAdditionalInfo::where('country_id', $country->id)
                ->whereNotIn('id', $sentIds)
                ->delete();

            foreach ($request->additional_info as $info) {
                if (empty($info['title'])) {
                    continue;
                }
                if (! empty($info['id'])) {
                    CountryAdditionalInfo::where('id', $info['id'])->update($info);
                } else {
                    $info['country_id'] = $country->id;
                    CountryAdditionalInfo::create($info);
                }
            }
        }

        // === FAQs (hasMany) ===
        if ($request->has('faqs')) {
            $sentIds = collect($request->faqs)->pluck('id')->filter()->toArray();

            CountryFaq::where('country_id', $country->id)
                ->whereNotIn('id', $sentIds)
                ->delete();

            $questionNumber = 1;
            foreach ($request->faqs as $faq) {
                if (empty($faq['question'])) {
                    continue;
                }
                if (! empty($faq['id'])) {
                    CountryFaq::where('id', $faq['id'])->update([
                        'question_number' => $questionNumber++,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                } else {
                    CountryFaq::create([
                        'country_id' => $country->id,
                        'question_number' => $questionNumber++,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                }
            }
        }

        // === SEO (hasOne) ===
        if (! empty($validated['seo'])) {
            if ($country->seo) {
                $country->seo->update($validated['seo']);
            } else {
                $country->seo()->create($validated['seo']);
            }
        }

        return response()->json([
            'message' => 'Country updated successfully',
            'country' => $country->fresh(),
        ], 200);
    }

    /**
     * Remove the specified resource from array of object tables.
     */
    public function partialRemove(Request $request, $countryId)
    {
        // Events delete
        if ($request->has('deleted_event_ids')) {
            CountryEvent::whereIn('id', $request->deleted_event_ids)
                ->where('country_id', $countryId)
                ->delete();
        }

        // Seasons delete
        if ($request->has('deleted_season_ids')) {
            CountrySeason::whereIn('id', $request->deleted_season_ids)
                ->where('country_id', $countryId)
                ->delete();
        }

        // FAQs delete
        if ($request->has('deleted_faq_ids')) {
            CountryFaq::whereIn('id', $request->deleted_faq_ids)
                ->where('country_id', $countryId)
                ->delete();
        }

        // Additional Info delete
        if ($request->has('deleted_additional_info_ids')) {
            CountryAdditionalInfo::whereIn('id', $request->deleted_additional_info_ids)
                ->where('country_id', $countryId)
                ->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Selected records removed successfully',
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Country::findOrFail($id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Country deleted successfully',
        ]);
    }

    /**
     * Bulk delete multiple countries.
     */
    public function bulkDelete(Request $request)
    {
        $validated = $request->validate([
            'country_ids' => 'required|array|min:1',
            'country_ids.*' => 'integer|exists:countries,id',
        ]);

        try {
            $count = Country::whereIn('id', $validated['country_ids'])->delete();

            return response()->json([
                'success' => true,
                'message' => "{$count} countries deleted successfully",
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Failed to delete countries: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get counts for all destination types
     */
    public function getDestinationsCounts()
    {
        $counts = [
            'countries' => \App\Models\Country::count(),
            'states' => \App\Models\State::count(),
            'cities' => \App\Models\City::count(),
            'places' => \App\Models\Place::count(),
        ];

        return response()->json([
            'success' => true,
            'data' => $counts,
        ]);
    }
}
