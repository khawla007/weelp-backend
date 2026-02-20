<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\PlaceMediaGallery;
use App\Models\PlaceLocationDetail;
use App\Models\PlaceTravelInfo;
use App\Models\PlaceSeason;
use App\Models\PlaceEvent;
use App\Models\PlaceAdditionalInfo;
use App\Models\PlaceFaq;
use App\Models\PlaceSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PlaceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     return response()->json(Place::all());
    // }

    public function index(Request $request)
    {
        $query = Place::query()->with('mediaGallery.media');
    
        // Name search
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        // Pagination (perPage fix)
        $perPage = 4;
        $places = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $request->input('page', 1));
    
        // Transform response
        $data = $places->map(function ($place) {
            return [
                'id' => $place->id,
                'name' => $place->name,
                'code' => $place->code,
                'slug' => $place->slug,
                'type' => $place->type,
                'description' => $place->description,
                'feature_image' => $place->feature_image,
                'featured_destination' => $place->featured_destination,
                // Custom Media format
                'media_gallery' => $place->mediaGallery->map(function ($gallery) {
                    return [
                        'id' => $gallery->id,
                        'place_id' => $gallery->place_id,
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
            'total' => $places->total(),
            'per_page' => $places->perPage(),
            'last_page' => $places->lastPage(),
            'current_page' => $places->currentPage(), 
        ]);
    }
    
    /**
     * List of Places dropdown
    */
    public function placeList()
    {
        try {
            $places = Place::select('id', 'name', 'type')->orderBy('name')->get();

            if ($places->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No Place found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Place list fetched successfully',
                'data' => $places
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching Place list',
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
            // Place fields
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'slug' => 'required|string|max:255',
            // 'type' => 'required|string|max:255',
            'city_id' => 'required|integer|exists:cities,id',
            'description' => 'nullable|string',
            'feature_image' => 'nullable|url',
            'featured_destination' => 'boolean',

            // Media (array of objects)
            'media_gallery'     => 'nullable|array',

            // Location Details
            'location_details.latitude' => 'nullable|string',
            'location_details.longitude' => 'nullable|string',
            'location_details.capital_Place' => 'nullable|string',
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

        $exists = Place::where('name', $request->name)
            ->orWhere('slug', $request->slug)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This Place already exists, please choose another name.'
            ], 422); // 422 = Unprocessable Entity (validation error)
        }
        // Create Place
        $place = Place::create([
            'name'                 => $validated['name'],
            'code'                 => $validated['code'],
            'slug'                 => $validated['slug'],
            // 'type'                 => $validated['type'],
            'city_id'              => $validated['city_id'],
            'description'          => $validated['description'] ?? null,
            'feature_image'        => $validated['feature_image'] ?? null,
            'featured_destination' => $validated['featured_destination'] ?? false,
        ]);

        // Media Details
        if (!empty($validated['media_gallery'])) {
            foreach ($validated['media_gallery'] as $media) {
                PlaceMediaGallery::create([
                    'place_id' => $place->id,
                    'media_id' => $media['media_id'],
                ]);
            }
        }

        // Location Details
        if (!empty($validated['location_details'])) {
            $validated['location_details']['place_id'] = $place->id;
            PlaceLocationDetail::create($validated['location_details']);
        }

        // Travel Info
        if (!empty($validated['travel_info'])) {
            $validated['travel_info']['place_id'] = $place->id;
            PlaceTravelInfo::create($validated['travel_info']);
        }

        // Season
        if ($request->has('seasons')) {
            foreach ($request->seasons as $season) {
                $place->seasons()->create($season);
            }
        }

        // Event
        if ($request->has('events')) {
            foreach ($request->events as $event) {
                $place->events()->create($event);
            }
        }

        // Additional Info
        if (!empty($validated['additional_info'])) {
            foreach ($validated['additional_info'] as $additional) {
                $additional['place_id'] = $place->id;
                PlaceAdditionalInfo::create($additional);
            }
        }

        // FAQs
        if (!empty($validated['faqs'])) {
            $questionNumber = 1;
            foreach ($validated['faqs'] as $faq) {
                PlaceFaq::create([
                    'place_id'        => $place->id,
                    'question_number' => $questionNumber++,
                    'question'        => $faq['question'],
                    'answer'          => $faq['answer'],
                ]);
            }
        }

        // SEO
        if (!empty($validated['seo'])) {
            $validated['seo']['place_id'] = $place->id;
            PlaceSeo::create($validated['seo']);
        }

        return response()->json([
            'message' => 'Place created successfully',
            'place' => $place
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // return response()->json(Place::findOrFail($id));
        $place = Place::with([
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
        if ($place->mediaGallery && $place->mediaGallery->count()) {
            $place->media_gallery = $place->mediaGallery->map(function ($gallery) {
                return [
                    'id' => $gallery->id,
                    'place_id' => $gallery->place_id,
                    'media_id' => $gallery->media_id,
                    'name' => $gallery->media->name ?? null,
                    'alt_text' => $gallery->media->alt_text ?? null,
                    'url' => $gallery->media->url ?? null,
                ];
            })->values();
            unset($place->mediaGallery); // nested relation hatane ke liye
        }

        if (!$place) {
            return response()->json(['message' => 'Place not found'], 404);
        }
    
        return response()->json($place);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $place = Place::findOrFail($id);
    
        $validated = $request->validate([
            // Place fields
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:10',
            'slug' => 'nullable|string|max:255',
            // 'type' => 'nullable|string|max:255',
            'city_id' => 'nullable|integer|exists:cities,id',
            'description' => 'nullable|string',
            'feature_image' => 'nullable|url',
            'featured_destination' => 'boolean',
    
            // Media (array of objects)
            'media_gallery'     => 'nullable|array',
    
            // Location Details
            'location_details.latitude' => 'nullable|string',
            'location_details.longitude' => 'nullable|string',
            'location_details.capital_Place' => 'nullable|string',
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
            'seasons.*.id' => 'nullable|integer|exists:place_seasons,id',
            'seasons.*.name' => 'nullable|string',
            'seasons.*.months' => 'nullable|array',
            'seasons.*.weather' => 'nullable|string',
            'seasons.*.activities' => 'nullable|array',
    
            // Event (array of objects)
            'events' => 'nullable|array',
            'events.*.id' => 'nullable|integer|exists:place_events,id',
            'events.*.name' => 'nullable|string',
            'events.*.type' => 'nullable|array',
            'events.*.date' => 'nullable|date',
            'events.*.location' => 'nullable|string',
            'events.*.description' => 'nullable|string',
    
            // Additional Info
            'additional_info' => 'nullable|array',
            'additional_info.*.id' => 'nullable|integer|exists:place_additional_infos,id',
            'additional_info.*.title' => 'required|string',
            'additional_info.*.content' => 'required|string',
    
            // FAQs
            'faqs' => 'nullable|array',
            'faqs.*.id' => 'nullable|integer|exists:place_faqs,id',
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
    
        // === Place main fields update ===
        $place->update([
            'name' => $validated['name'] ?? $place->name,
            'code' => $validated['code'] ?? $place->code,
            'slug' => $validated['slug'] ?? $place->slug,
            // 'type' => $validated['type'] ?? $place->slug,
            'city_id' => $validated['city_id'] ?? $place->city_id,
            'description' => $validated['description'] ?? $place->description,
            'feature_image' => $validated['feature_image'] ?? $place->feature_image,
            'featured_destination' => $validated['featured_destination'] ?? $place->featured_destination,
        ]);
    
        // === Media (delete old & insert new) ===
        if (isset($validated['media_gallery'])) {
            PlaceMediaGallery::where('place_id', $place->id)->delete();
            foreach ($validated['media_gallery'] as $media) {
                PlaceMediaGallery::create([
                    'place_id' => $place->id,
                    'media_id'    => $media['media_id'],
                ]);
            }
        }
    
        // === Location Details (hasOne) ===
        if (!empty($validated['location_details'])) {
            $place->locationDetails()
                ? $place->locationDetails->update($validated['location_details'])
                : $place->locationDetails()->create($validated['location_details']);
        }
    
        // === Travel Info (hasOne) ===
        if (!empty($validated['travel_info'])) {
            $place->travelInfo()
                ? $place->travelInfo->update($validated['travel_info'])
                : $place->travelInfo()->create($validated['travel_info']);
        }
    
        // === Seasons (hasMany) ===
        if ($request->has('seasons')) {
            $sentIds = collect($request->seasons)->pluck('id')->filter()->toArray();
    
            // delete missing
            PlaceSeason::where('place_id', $place->id)
                ->whereNotIn('id', $sentIds)
                ->delete();
    
            foreach ($request->seasons as $season) {
                if (!empty($season['id'])) {
                    PlaceSeason::where('id', $season['id'])->update($season);
                } else {
                    $place->seasons()->create($season);
                }
            }
        }
    
        // === Events (hasMany) ===
        if ($request->has('events')) {
            $sentIds = collect($request->events)->pluck('id')->filter()->toArray();
    
            PlaceEvent::where('place_id', $place->id)
                ->whereNotIn('id', $sentIds)
                ->delete();
    
            foreach ($request->events as $event) {
                if (!empty($event['id'])) {
                    PlaceEvent::where('id', $event['id'])->update($event);
                } else {
                    $place->events()->create($event);
                }
            }
        }
    
        // === Additional Info (hasMany) ===
        if ($request->has('additional_info')) {
            $sentIds = collect($request->additional_info)->pluck('id')->filter()->toArray();
    
            PlaceAdditionalInfo::where('place_id', $place->id)
                ->whereNotIn('id', $sentIds)
                ->delete();
    
            foreach ($request->additional_info as $info) {
                if (!empty($info['id'])) {
                    PlaceAdditionalInfo::where('id', $info['id'])->update($info);
                } else {
                    $info['place_id'] = $place->id;
                    PlaceAdditionalInfo::create($info);
                }
            }
        }
    
        // === FAQs (hasMany) ===
        if ($request->has('faqs')) {
            $sentIds = collect($request->faqs)->pluck('id')->filter()->toArray();
    
            PlaceFaq::where('place_id', $place->id)
                ->whereNotIn('id', $sentIds)
                ->delete();
    
            $questionNumber = 1;
            foreach ($request->faqs as $faq) {
                if (!empty($faq['id'])) {
                    PlaceFaq::where('id', $faq['id'])->update([
                        'question_number' => $questionNumber++,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                } else {
                    PlaceFaq::create([
                        'place_id' => $place->id,
                        'question_number' => $questionNumber++,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                }
            }
        }
    
        // === SEO (hasOne) ===
        if (!empty($validated['seo'])) {
            $place->seo()
                ? $place->seo->update($validated['seo'])
                : $place->seo()->create($validated['seo']);
        }
    
        return response()->json([
            'message' => 'Place updated successfully',
            'place' => $place->fresh()
        ], 200);
    }    

    /**
     * Remove the specified resource from array of object tables.
    */
    public function partialRemove(Request $request, $placeId)
    {
        // Events delete
        if ($request->has('deleted_event_ids')) {
            PlaceEvent::whereIn('id', $request->deleted_event_ids)
                ->where('place_id', $placeId)
                ->delete();
        }
    
        // Seasons delete
        if ($request->has('deleted_season_ids')) {
            PlaceSeason::whereIn('id', $request->deleted_season_ids)
                ->where('place_id', $placeId)
                ->delete();
        }
    
        // FAQs delete
        if ($request->has('deleted_faq_ids')) {
            PlaceFaq::whereIn('id', $request->deleted_faq_ids)
                ->where('place_id', $placeId)
                ->delete();
        }
    
        // Additional Info delete
        if ($request->has('deleted_additional_info_ids')) {
            PlaceAdditionalInfo::whereIn('id', $request->deleted_additional_info_ids)
                ->where('place_id', $placeId)
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
        Place::findOrFail($id)->delete();
        return response()->json(['message' => 'Place deleted successfully']);
    }
}
