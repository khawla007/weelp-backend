<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\State;
use App\Models\StateMediaGallery;
use App\Models\StateLocationDetail;
use App\Models\StateTravelInfo;
use App\Models\StateSeason;
use App\Models\StateEvent;
use App\Models\StateAdditionalInfo;
use App\Models\StateFaq;
use App\Models\StateSeo;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class StateController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    // public function index()
    // {
    //     return response()->json(State::all());
    // }

    public function index(Request $request)
    {
        $query = State::query()->with('mediaGallery.media');
    
        // Name search
        if ($request->has('name') && !empty($request->name)) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }
        
        // Pagination (perPage fix)
        $perPage = 4;
        $states = $query->orderBy('id', 'desc')->paginate($perPage, ['*'], 'page', $request->input('page', 1));
    
        // Transform response
        $data = $states->map(function ($state) {
            return [
                'id' => $state->id,
                'name' => $state->name,
                'code' => $state->code,
                'slug' => $state->slug,
                'type' => $state->type,
                'description' => $state->description,
                'feature_image' => $state->feature_image,
                'featured_destination' => $state->featured_destination,
                // Custom Media format
                'media_gallery' => $state->mediaGallery->map(function ($gallery) {
                    return [
                        'id' => $gallery->id,
                        'state_id' => $gallery->state_id,
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
            'total' => $states->total(),
            'per_page' => $states->perPage(),
            'last_page' => $states->lastPage(),
            'current_page' => $states->currentPage(), 
        ]);
    }
    
    /**
     * List of states dropdown
    */
    public function stateList()
    {
        try {
            $states = State::select('id', 'name', 'type')->orderBy('name')->get();

            if ($states->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No State found',
                    'data' => []
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'State list fetched successfully',
                'data' => $states
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Something went wrong while fetching State list',
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
            // State fields
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10',
            'slug' => 'required|string|max:255',
            // 'type' => 'required|string|max:255',
            'country_id' => 'required|integer|exists:countries,id',
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

        $exists = State::where('name', $request->name)
            ->orWhere('slug', $request->slug)
            ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'This State already exists, please choose another name.'
            ], 422); // 422 = Unprocessable Entity (validation error)
        }
        // Create State
        $state = State::create([
            'name' => $validated['name'],
            'code' => $validated['code'],
            'slug' => $validated['slug'],
            // 'type' => $validated['type'],
            'country_id' => $validated['country_id'],
            'description' => $validated['description'] ?? null,
            'feature_image' => $validated['feature_image'] ?? null,
            'featured_destination' => $validated['featured_destination'] ?? false,
        ]);

        // Media Details
        if (!empty($validated['media_gallery'])) {
            foreach ($validated['media_gallery'] as $media) {
                StateMediaGallery::create([
                    'state_id' => $state->id,
                    'media_id'    => $media['media_id'],
                ]);
            }
        }

        // Location Details
        if (!empty($validated['location_details'])) {
            $validated['location_details']['state_id'] = $state->id;
            StateLocationDetail::create($validated['location_details']);
        }

        // Travel Info
        if (!empty($validated['travel_info'])) {
            $validated['travel_info']['state_id'] = $state->id;
            StateTravelInfo::create($validated['travel_info']);
        }

        // Season
        if ($request->has('seasons')) {
            foreach ($request->seasons as $season) {
                $state->seasons()->create($season);
            }
        }

        // Event
        if ($request->has('events')) {
            foreach ($request->events as $event) {
                $state->events()->create($event);
            }
        }

        // Additional Info
        if (!empty($validated['additional_info'])) {
            foreach ($validated['additional_info'] as $additional) {
                $additional['state_id'] = $state->id;
                StateAdditionalInfo::create($additional);
            }
        }

        // FAQs
        if (!empty($validated['faqs'])) {
            $questionNumber = 1;
            foreach ($validated['faqs'] as $faq) {
                StateFaq::create([
                    'state_id' => $state->id,
                    'question_number' => $questionNumber++,
                    'question' => $faq['question'],
                    'answer' => $faq['answer'],
                ]);
            }
        }

        // SEO
        if (!empty($validated['seo'])) {
            $validated['seo']['state_id'] = $state->id;
            StateSeo::create($validated['seo']);
        }

        return response()->json([
            'message' => 'State created successfully',
            'state' => $state
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // return response()->json(State::findOrFail($id));
        $state = State::with([
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
        if ($state->mediaGallery && $state->mediaGallery->count()) {
            $state->media_gallery = $state->mediaGallery->map(function ($gallery) {
                return [
                    'id' => $gallery->id,
                    'state_id' => $gallery->state_id,
                    'media_id' => $gallery->media_id,
                    'name' => $gallery->media->name ?? null,
                    'alt_text' => $gallery->media->alt_text ?? null,
                    'url' => $gallery->media->url ?? null,
                ];
            })->values();
            unset($state->mediaGallery); // nested relation hatane ke liye
        }

        if (!$state) {
            return response()->json(['message' => 'State not found'], 404);
        }
    
        return response()->json($state);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $state = State::findOrFail($id);
    
        $validated = $request->validate([
            // State fields
            'name' => 'nullable|string|max:255',
            'code' => 'nullable|string|max:10',
            'slug' => 'nullable|string|max:255',
            // 'type' => 'nullable|string|max:255',
            'country_id' => 'nullable|integer|exists:countries,id',
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
            'seasons.*.id' => 'nullable|integer|exists:state_seasons,id',
            'seasons.*.name' => 'nullable|string',
            'seasons.*.months' => 'nullable|array',
            'seasons.*.weather' => 'nullable|string',
            'seasons.*.activities' => 'nullable|array',
    
            // Event (array of objects)
            'events' => 'nullable|array',
            'events.*.id' => 'nullable|integer|exists:state_events,id',
            'events.*.name' => 'nullable|string',
            'events.*.type' => 'nullable|array',
            'events.*.date' => 'nullable|date',
            'events.*.location' => 'nullable|string',
            'events.*.description' => 'nullable|string',
    
            // Additional Info
            'additional_info' => 'nullable|array',
            'additional_info.*.id' => 'nullable|integer|exists:state_additional_infos,id',
            'additional_info.*.title' => 'required|string',
            'additional_info.*.content' => 'required|string',
    
            // FAQs
            'faqs' => 'nullable|array',
            'faqs.*.id' => 'nullable|integer|exists:state_faqs,id',
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
    
        // === State main fields update ===
        $state->update([
            'name' => $validated['name'] ?? $state->name,
            'code' => $validated['code'] ?? $state->code,
            'slug' => $validated['slug'] ?? $state->slug,
            // 'type' => $validated['type'] ?? $state->type,
            'country_id' => $validated['country_id'] ?? $state->country_id,
            'description' => $validated['description'] ?? $state->description,
            'feature_image' => $validated['feature_image'] ?? $state->feature_image,
            'featured_destination' => $validated['featured_destination'] ?? $state->featured_destination,
        ]);
    
        // === Media (delete old & insert new) ===
        if (isset($validated['media_gallery'])) {
            StateMediaGallery::where('state_id', $state->id)->delete();
            foreach ($validated['media_gallery'] as $media) {
                StateMediaGallery::create([
                    'state_id' => $state->id,
                    'media_id'    => $media['media_id'],
                ]);
            }
        }
    
        // === Location Details (hasOne) ===
        if (!empty($validated['location_details'])) {
            $state->locationDetails()
                ? $state->locationDetails->update($validated['location_details'])
                : $state->locationDetails()->create($validated['location_details']);
        }
    
        // === Travel Info (hasOne) ===
        if (!empty($validated['travel_info'])) {
            $state->travelInfo()
                ? $state->travelInfo->update($validated['travel_info'])
                : $state->travelInfo()->create($validated['travel_info']);
        }
    
        // === Seasons (hasMany) ===
        if ($request->has('seasons')) {
            $sentIds = collect($request->seasons)->pluck('id')->filter()->toArray();
    
            // delete missing
            StateSeason::where('state_id', $state->id)
                ->whereNotIn('id', $sentIds)
                ->delete();
    
            foreach ($request->seasons as $season) {
                if (!empty($season['id'])) {
                    StateSeason::where('id', $season['id'])->update($season);
                } else {
                    $state->seasons()->create($season);
                }
            }
        }
    
        // === Events (hasMany) ===
        if ($request->has('events')) {
            $sentIds = collect($request->events)->pluck('id')->filter()->toArray();
    
            StateEvent::where('state_id', $state->id)
                ->whereNotIn('id', $sentIds)
                ->delete();
    
            foreach ($request->events as $event) {
                if (!empty($event['id'])) {
                    StateEvent::where('id', $event['id'])->update($event);
                } else {
                    $state->events()->create($event);
                }
            }
        }
    
        // === Additional Info (hasMany) ===
        if ($request->has('additional_info')) {
            $sentIds = collect($request->additional_info)->pluck('id')->filter()->toArray();
    
            StateAdditionalInfo::where('state_id', $state->id)
                ->whereNotIn('id', $sentIds)
                ->delete();
    
            foreach ($request->additional_info as $info) {
                if (!empty($info['id'])) {
                    StateAdditionalInfo::where('id', $info['id'])->update($info);
                } else {
                    $info['state_id'] = $state->id;
                    StateAdditionalInfo::create($info);
                }
            }
        }
    
        // === FAQs (hasMany) ===
        if ($request->has('faqs')) {
            $sentIds = collect($request->faqs)->pluck('id')->filter()->toArray();
    
            StateFaq::where('state_id', $state->id)
                ->whereNotIn('id', $sentIds)
                ->delete();
    
            $questionNumber = 1;
            foreach ($request->faqs as $faq) {
                if (!empty($faq['id'])) {
                    StateFaq::where('id', $faq['id'])->update([
                        'question_number' => $questionNumber++,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                } else {
                    StateFaq::create([
                        'state_id' => $state->id,
                        'question_number' => $questionNumber++,
                        'question' => $faq['question'],
                        'answer' => $faq['answer'],
                    ]);
                }
            }
        }
    
        // === SEO (hasOne) ===
        if (!empty($validated['seo'])) {
            $state->seo()
                ? $state->seo->update($validated['seo'])
                : $state->seo()->create($validated['seo']);
        }
    
        return response()->json([
            'message' => 'State updated successfully',
            'state' => $state->fresh()
        ], 200);
    }    

    /**
     * Remove the specified resource from array of object tables.
    */
    public function partialRemove(Request $request, $stateId)
    {
        // Events delete
        if ($request->has('deleted_event_ids')) {
            StateEvent::whereIn('id', $request->deleted_event_ids)
                ->where('state_id', $stateId)
                ->delete();
        }
    
        // Seasons delete
        if ($request->has('deleted_season_ids')) {
            StateSeason::whereIn('id', $request->deleted_season_ids)
                ->where('state_id', $stateId)
                ->delete();
        }
    
        // FAQs delete
        if ($request->has('deleted_faq_ids')) {
            StateFaq::whereIn('id', $request->deleted_faq_ids)
                ->where('state_id', $stateId)
                ->delete();
        }
    
        // Additional Info delete
        if ($request->has('deleted_additional_info_ids')) {
            StateAdditionalInfo::whereIn('id', $request->deleted_additional_info_ids)
                ->where('state_id', $stateId)
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
        State::findOrFail($id)->delete();
        return response()->json(['message' => 'State deleted successfully']);
    }
}
