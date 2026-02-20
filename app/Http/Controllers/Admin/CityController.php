<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\City;
use App\Models\CityMediaGallery;
use App\Models\CityLocationDetail;
use App\Models\CityTravelInfo;
use App\Models\CitySeason;
use App\Models\CityEvent;
use App\Models\CityAdditionalInfo;
use App\Models\CityFaq;
use App\Models\CitySeo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CityController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     return response()->json(City::all());
    // }

    public function index(Request $request)
    {
        $query = City::query()->with('mediaGallery.media');
    
        // Name search
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        // Pagination (perPage fix)
        $perPage = 4;
        $cities = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $request->input('page', 1));
    
        // Transform response
        $data = $cities->map(function ($city) {
            return [
                'id' => $city->id,
                'name' => $city->name,
                'code' => $city->code,
                'slug' => $city->slug,
                'type' => $city->type,
                'description' => $city->description,
                'feature_image' => $city->feature_image,
                'featured_destination' => $city->featured_destination,
                // Custom Media format
                'media_gallery' => $city->mediaGallery->map(function ($gallery) {
                    return [
                        'id' => $gallery->id,
                        'city_id' => $gallery->city_id,
                        'media_id' => $gallery->media_id,
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
            'total' => $cities->total(),
            'per_page' => $cities->perPage(),
            'last_page' => $cities->lastPage(),
            'current_page' => $cities->currentPage(), 
        ]);
    }
    
    /**
     * List of Citys dropdown
    */
    public function cityList()
    {
        try {
            $cities = City::select('id', 'name', 'type')->orderBy('name')->get();

            if ($cities->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No City found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'City list fetched successfully',
                'data' => $cities
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching City list',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            // City fields
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'slug' => 'required|string|max:255',
            // 'type' => 'required|string|max:255',
            'state_id' => 'required|integer|exists:states,id',
            'description' => 'nullable|string',
            'feature_image' => 'nullable|url',
            'featured_destination' => 'boolean',

            // Media (array of objects)
            'media_gallery'     => 'nullable|array',

            // Location Details
            'location_details.latitude' => 'nullable|string',
            'location_details.longitude' => 'nullable|string',
            'location_details.capital_city' => 'nullable|string',
            'location_details.population' => 'nullable|integer',
            'location_details.currency' => 'nullable|string',
            'location_details.timezone' => 'nullable|string',
            'location_details.language' => 'nullable|array',
            'location_details.local_cuisine' => 'nullable|array',

            // Travel Info
            'travel_info.airport' => 'nullable|string',
            'travel_info.public_transportation' => 'nullable|array',
            'travel_info.taxi_available' => 'boolean',
            'travel_info.rental_cars_available' => 'boolean',
            'travel_info.hotels' => 'boolean',
            'travel_info.hostels' => 'boolean',
            'travel_info.apartments' => 'boolean',
            'travel_info.resorts' => 'boolean',
            'travel_info.visa_requirements' => 'nullable|string',
            'travel_info.best_time_to_visit' => 'nullable|string',
            'travel_info.travel_tips' => 'nullable|string',
            'travel_info.safety_information' => 'nullable|string',

            // Season (array of objects)
            'seasons' => 'nullable|array',
            'seasons.*.name' => 'nullable|string',
            'seasons.*.months' => 'nullable|array',
            'seasons.*.weather' => 'nullable|string',
            'seasons.*.activities' => 'nullable|array',

            // Event (array of objects)
            'events' => 'nullable|array',
            'events.*.name' => 'nullable|string',
            'events.*.type' => 'nullable|array',
            'events.*.date' => 'nullable|date',
            'events.*.location' => 'nullable|string',
            'events.*.description' => 'nullable|string',

            // Additional Info
            'additional_info' => 'nullable|array',
            'additional_info.*.title' => 'required|string',
            'additional_info.*.content' => 'required|string',

            // FAQs
            'faqs' => 'array',
            'faqs.*.question' => 'required|string',
            'faqs.*.answer' => 'required|string',

            // SEO
            'seo.meta_title' => 'nullable|string',
            'seo.meta_description' => 'nullable|string',
            'seo.keywords' => 'nullable|string',
            'seo.og_image_url' => 'nullable|url',
            'seo.canonical_url' => 'nullable|url',
            'seo.schema_type' => 'nullable|string',
            'seo.schema_data' => 'nullable|array',
        ]);

        $exists = City::where('name', $request->name)
            ->orWhere('slug', $request->slug)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This City already exists, please choose another name.'
            ], 422); // 422 = Unprocessable Entity (validation error)
        }
        // Create City
        $city = City::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'slug' => $validated['slug'],
            // 'type' => $validated['type'],
            'state_id' => $validated['state_id'],
            'description' => $validated['description'] ?? null,
            'feature_image' => $validated['feature_image'] ?? null,
            'featured_destination' => $validated['featured_destination'] ?? false,
        ]);

        // Media Details
        if (!empty($validated['media_gallery'])) {
            foreach ($validated['media_gallery'] as $media) {
                CityMediaGallery::create([
                    'city_id' => $city->id,
                    'media_id'    => $media['media_id'],
                ]);
            }
        }

        // Location Details
        if (!empty($validated['location_details'])) {
            $validated['location_details']['city_id'] = $city->id;
            CityLocationDetail::create($validated['location_details']);
        }

        // Travel Info
        if (!empty($validated['travel_info'])) {
            $validated['travel_info']['city_id'] = $city->id;
            CityTravelInfo::create($validated['travel_info']);
        }

        // Season
        if ($request->has('seasons')) {
            foreach ($request->seasons as $season) {
                $city->seasons()->create($season);
            }
        }

        // Event
        if ($request->has('events')) {
            foreach ($request->events as $event) {
                $city->events()->create($event);
            }
        }

        // Additional Info
        if (!empty($validated['additional_info'])) {
            foreach ($validated['additional_info'] as $additional) {
                $additional['city_id'] = $city->id;
                CityAdditionalInfo::create($additional);
            }
        }

        // FAQs
        if (!empty($validated['faqs'])) {
            $questionNumber = 1;
            foreach ($validated['faqs'] as $faq) {
                CityFaq::create([
                    'city_id' => $city->id,
                    'question_number' => $questionNumber++,
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                ]);
            }
        }

        // SEO
        if (!empty($validated['seo'])) {
            $validated['seo']['city_id'] = $city->id;
            CitySeo::create($validated['seo']);
        }

        return response()->json([
            'message' => 'City created successfully',
            'City' => $city
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // return response()->json(City::findOrFail($id));
        $city = City::with([
            'mediaGallery.media',
            'locationDetails',
            'travelInfo',
            'seasons',
            'events',
            'additionalInfo',
            'faqs',
            'seo'
        ])->find($id);
    
        // media_gallery ko transform karna
        if ($city->mediaGallery && $city->mediaGallery->count()) {
            $city->media_gallery = $city->mediaGallery->map(function ($gallery) {
                return [
                    'id' => $gallery->id,
                    'city_id' => $gallery->city_id,
                    'media_id' => $gallery->media_id,
                    'name' => $gallery->media->name ?? null,
                    'alt_text' => $gallery->media->alt_text ?? null,
                    'url' => $gallery->media->url ?? null,
                ];
            })->values();
            unset($city->mediaGallery); // nested relation hatane ke liye
        }

        if (!$city) {
            return response()->json(['message' => 'City not found'], 404);
        }
    
        return response()->json($city);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $city = City::findOrFail($id);
    
        $validated = $request->validate([
            // City fields
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:10',
            'slug' => 'nullable|string|max:255',
            // 'type' => 'nullable|string|max:255',
            'state_id' => 'nullable|integer|exists:states,id',
            'description' => 'nullable|string',
            'feature_image' => 'nullable|url',
            'featured_destination' => 'boolean',
    
            // Media (array of objects)
            'media_gallery'     => 'nullable|array',
    
            // Location Details
            'location_details.latitude' => 'nullable|string',
            'location_details.longitude' => 'nullable|string',
            'location_details.capital_city' => 'nullable|string',
            'location_details.population' => 'nullable|integer',
            'location_details.currency' => 'nullable|string',
            'location_details.timezone' => 'nullable|string',
            'location_details.language' => 'nullable|array',
            'location_details.local_cuisine' => 'nullable|array',
    
            // Travel Info
            'travel_info.airport' => 'nullable|string',
            'travel_info.public_transportation' => 'nullable|array',
            'travel_info.taxi_available' => 'boolean',
            'travel_info.rental_cars_available' => 'boolean',
            'travel_info.hotels' => 'boolean',
            'travel_info.hostels' => 'boolean',
            'travel_info.apartments' => 'boolean',
            'travel_info.resorts' => 'boolean',
            'travel_info.visa_requirements' => 'nullable|string',
            'travel_info.best_time_to_visit' => 'nullable|string',
            'travel_info.travel_tips' => 'nullable|string',
            'travel_info.safety_information' => 'nullable|string',
    
            // Season (array of objects)
            'seasons' => 'nullable|array',
            'seasons.*.id' => 'nullable|integer|exists:city_seasons,id',
            'seasons.*.name' => 'nullable|string',
            'seasons.*.months' => 'nullable|array',
            'seasons.*.weather' => 'nullable|string',
            'seasons.*.activities' => 'nullable|array',
    
            // Event (array of objects)
            'events' => 'nullable|array',
            'events.*.id' => 'nullable|integer|exists:city_events,id',
            'events.*.name' => 'nullable|string',
            'events.*.type' => 'nullable|array',
            'events.*.date' => 'nullable|date',
            'events.*.location' => 'nullable|string',
            'events.*.description' => 'nullable|string',
    
            // Additional Info
            'additional_info' => 'nullable|array',
            'additional_info.*.id' => 'nullable|integer|exists:city_additional_infos,id',
            'additional_info.*.title' => 'required|string',
            'additional_info.*.content' => 'required|string',
    
            // FAQs
            'faqs' => 'nullable|array',
            'faqs.*.id' => 'nullable|integer|exists:city_faqs,id',
            'faqs.*.question' => 'required|string',
            'faqs.*.answer' => 'required|string',
    
            // SEO
            'seo.meta_title' => 'nullable|string',
            'seo.meta_description' => 'nullable|string',
            'seo.keywords' => 'nullable|string',
            'seo.og_image_url' => 'nullable|url',
            'seo.canonical_url' => 'nullable|url',
            'seo.schema_type' => 'nullable|string',
            'seo.schema_data' => 'nullable|array',
        ]);
    
        // === City main fields update ===
        $city->update([
            'name' => $validated['name'] ?? $city->name,
            'code' => $validated['code'] ?? $city->code,
            'slug' => $validated['slug'] ?? $city->slug,
            // 'type' => $validated['type'] ?? $city->slug,
            'state_id' => $validated['state_id'] ?? $city->state_id,
            'description' => $validated['description'] ?? $city->description,
            'feature_image' => $validated['feature_image'] ?? $city->feature_image,
            'featured_destination' => $validated['featured_destination'] ?? $city->featured_destination,
        ]);
    
        // === Media (delete old & insert new) ===
        if (isset($validated['media_gallery'])) {
            CityMediaGallery::where('city_id', $city->id)->delete();
            foreach ($validated['media_gallery'] as $media) {
                CityMediaGallery::create([
                    'city_id' => $city->id,
                    'media_id'    => $media['media_id'],
                ]);
            }
        }
    
        // === Location Details (hasOne) ===
        if (!empty($validated['location_details'])) {
            $city->locationDetails()
                ? $city->locationDetails->update($validated['location_details'])
                : $city->locationDetails()->create($validated['location_details']);
        }
    
        // === Travel Info (hasOne) ===
        if (!empty($validated['travel_info'])) {
            $city->travelInfo()
                ? $city->travelInfo->update($validated['travel_info'])
                : $city->travelInfo()->create($validated['travel_info']);
        }
    
        // === Seasons (hasMany) ===
        if ($request->has('seasons')) {
            $sentIds = collect($request->seasons)->pluck('id')->filter()->toArray();
    
            // delete missing
            CitySeason::where('city_id', $city->id)
                ->whereNotIn('id', $sentIds)
                ->delete();
    
            foreach ($request->seasons as $season) {
                if (!empty($season['id'])) {
                    CitySeason::where('id', $season['id'])->update($season);
                } else {
                    $city->seasons()->create($season);
                }
            }
        }
    
        // === Events (hasMany) ===
        if ($request->has('events')) {
            $sentIds = collect($request->events)->pluck('id')->filter()->toArray();
    
            CityEvent::where('city_id', $city->id)
                ->whereNotIn('id', $sentIds)
                ->delete();
    
            foreach ($request->events as $event) {
                if (!empty($event['id'])) {
                    CityEvent::where('id', $event['id'])->update($event);
                } else {
                    $city->events()->create($event);
                }
            }
        }
    
        // === Additional Info (hasMany) ===
        if ($request->has('additional_info')) {
            $sentIds = collect($request->additional_info)->pluck('id')->filter()->toArray();
    
            CityAdditionalInfo::where('city_id', $city->id)
                ->whereNotIn('id', $sentIds)
                ->delete();
    
            foreach ($request->additional_info as $info) {
                if (!empty($info['id'])) {
                    CityAdditionalInfo::where('id', $info['id'])->update($info);
                } else {
                    $info['city_id'] = $city->id;
                    CityAdditionalInfo::create($info);
                }
            }
        }
    
        // === FAQs (hasMany) ===
        if ($request->has('faqs')) {
            $sentIds = collect($request->faqs)->pluck('id')->filter()->toArray();
    
            CityFaq::where('city_id', $city->id)
                ->whereNotIn('id', $sentIds)
                ->delete();
    
            $questionNumber = 1;
            foreach ($request->faqs as $faq) {
                if (!empty($faq['id'])) {
                    CityFaq::where('id', $faq['id'])->update([
                        'question_number' => $questionNumber++,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                } else {
                    CityFaq::create([
                        'city_id' => $city->id,
                        'question_number' => $questionNumber++,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                }
            }
        }
    
        // === SEO (hasOne) ===
        if (!empty($validated['seo'])) {
            $city->seo()
                ? $city->seo->update($validated['seo'])
                : $city->seo()->create($validated['seo']);
        }
    
        return response()->json([
            'message' => 'City updated successfully',
            'City' => $city->fresh()
        ], 200);
    }    

    /**
     * Remove the specified resource from array of object tables.
    */
    public function partialRemove(Request $request, $cityId)
    {
        // Events delete
        if ($request->has('deleted_event_ids')) {
            CityEvent::whereIn('id', $request->deleted_event_ids)
                ->where('city_id', $cityId)
                ->delete();
        }
    
        // Seasons delete
        if ($request->has('deleted_season_ids')) {
            CitySeason::whereIn('id', $request->deleted_season_ids)
                ->where('city_id', $cityId)
                ->delete();
        }
    
        // FAQs delete
        if ($request->has('deleted_faq_ids')) {
            CityFaq::whereIn('id', $request->deleted_faq_ids)
                ->where('city_id', $cityId)
                ->delete();
        }
    
        // Additional Info delete
        if ($request->has('deleted_additional_info_ids')) {
            CityAdditionalInfo::whereIn('id', $request->deleted_additional_info_ids)
                ->where('city_id', $cityId)
                ->delete();
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Selected records removed successfully'
        ]);
    } 

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        City::findOrFail($id)->delete();
        return response()->json(['message' => 'City deleted successfully']);
    }
}
