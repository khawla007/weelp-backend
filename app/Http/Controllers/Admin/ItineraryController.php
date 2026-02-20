<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Itinerary;
use App\Models\ItineraryInfomation;
use App\Models\ItineraryLocation;
use App\Models\ItinerarySchedule;
use App\Models\ItineraryActivity;
use App\Models\ItineraryTransfer;
use App\Models\ItineraryBasePricing;
use App\Models\ItineraryPriceVariation;
use App\Models\ItineraryBlackoutDate;
use App\Models\ItineraryInclusionExclusion;
use App\Models\ItineraryMediaGallery;
use App\Models\ItineraryCategory;
use App\Models\ItineraryAttribute;
use App\Models\ItineraryTag;
use App\Models\ItineraryFaq;
use App\Models\ItinerarySeo;
use App\Models\ItineraryAvailability;
use App\Models\ItineraryAddon;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Tag;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Validator;


class ItineraryController extends Controller
{

    /**
     * Display a listing of the itineraries.
    */
    public function index(Request $request)
    {
        $perPage        = 3; 
        $page           = $request->get('page', 1); 

        $search           = $request->get('search'); // Search by activity name
        $categorySlug   = $request->get('category');
        $difficulty     = $request->get('difficulty_level');
        $duration       = $request->get('duration');
        $ageGroup       = $request->get('age_restriction');
        $season         = $request->get('season');
        $minPrice       = $request->get('min_price', 0);
        $maxPrice       = $request->get('max_price');
        $sortBy         = $request->get('sort_by', 'id_asc'); // Default: Newest First

        $category       = $categorySlug ? Category::where('slug', $categorySlug)->first() : null;
        $categoryId     = $category ? $category->id : null;

        $difficultyAttr = Attribute::where('slug', 'difficulty-level')->first();
        $durationAttr   = Attribute::where('slug', 'duration')->first();
        $ageGroupAttr   = Attribute::where('slug', 'age-restriction')->first();

        $query = Itinerary::query()
            ->select('itineraries.*')  
            ->join('itinerary_base_pricing', 'itinerary_base_pricing.itinerary_id', '=', 'itineraries.id') 
            ->join('itinerary_price_variations', 'itinerary_price_variations.base_pricing_id', '=', 'itinerary_base_pricing.id')
            ->with([
                'categories.category', 
                'locations.city', 
                'basePricing.variations', 
                'attributes.attribute:id,name',
                'mediaGallery.media', 'addons.addon'
            ])

            ->when($search, fn($query) =>
                $query->where('itineraries.name', 'like', "%{$search}%")
            )   
            
            ->when($categoryId, fn($query) => 
                $query->whereHas('categories', fn($q) => 
                    $q->where('category_id', $categoryId)
                )
            )
            ->when($difficulty && $difficultyAttr, fn($query) => 
                $query->whereHas('attributes', fn($q) => 
                    $q->where('attribute_id', $difficultyAttr->id)
                    ->where('attribute_value', $difficulty)
                )
            )
            ->when($duration && $durationAttr, fn($query) => 
                $query->whereHas('attributes', fn($q) => 
                    $q->where('attribute_id', $durationAttr->id)
                    ->where('attribute_value', $duration)
                )
            )
            ->when($ageGroup && $ageGroupAttr, fn($query) => 
                $query->whereHas('attributes', fn($q) => 
                    $q->where('attribute_id', $ageGroupAttr->id)
                    ->where('attribute_value', $ageGroup)
                )
            )
            ->when($season, fn($query) => 
                $query->whereHas('seasonalPricing', fn($q) => 
                    $q->where('season_name', $season)
                )
            )
            ->when($maxPrice !== null, fn($query) => 
                $query->whereHas('basePricing', fn($q) => 
                    $q->whereHas('variations', fn($q2) => 
                        $q2->whereBetween('sale_price', [$minPrice, $maxPrice])
                    )
                )
            );

        // Sorting logic
        switch ($sortBy) {
            case 'price_asc':
                $query->orderBy('itinerary_price_variations.sale_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('itinerary_price_variations.sale_price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('itineraries.name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('itineraries.name', 'desc');
                break;
            case 'id_asc':
                $query->orderBy('itineraries.id', 'asc');
                break;
            case 'id_desc':
                $query->orderBy('itineraries.id', 'desc');
                break;
            case 'featured':
                $query->orderByRaw('itineraries.featured_itinerary DESC');
                break;
            default:
                $query->orderBy('itineraries.id', 'desc');
                break;
        }

        $allItems       = $query->get();
        $paginatedItems = $allItems->forPage($page, $perPage);


        $transformed = $paginatedItems->map(function ($itinerary) {
            $data = $itinerary->toArray(); // keep all original fields
        
            // Replace transformed fields for addons
            $data['addons'] = collect($itinerary->addons)->map(function ($addon) {
                return [
                    'id'                      => $addon->id,
                    'addon_id'                => $addon->addon_id,
                    'addon_name'              => $addon->addon->name ?? null,
                    'addon_type'              => $addon->addon->type ?? null,
                    'addon_description'       => $addon->addon->description ?? null,
                    'addon_price'             => $addon->addon->price ?? null,
                    'addon_sale_price'        => $addon->addon->sale_price ?? null,
                    'addon_price_calculation' => $addon->addon->price_calculation ?? null,
                    'addon_active_status'     => $addon->addon->active_status ?? null,
                ];
            });
            
            // Replace transformed fields
            $data['locations'] = collect($itinerary->locations)->map(function ($location) {
                return [
                    'id'         => $location->id,
                    'city_id'    => $location->city_id,
                    'city_name'  => $location->city->name ?? null,
                ];
            });
        
            $data['media_gallery'] = collect($itinerary->mediaGallery)->map(function ($media) {
                return [
                    'id'         => $media->id,
                    'media_id'   => $media->media_id,
                    'name'       => $media->media->name ?? null,
                    'alt_text'   => $media->media->alt_text ?? null,
                    'url'        => $media->media->url ?? null,
                ];
            });
        
            $data['attributes'] = collect($itinerary->attributes)->map(function ($attribute) {
                return [
                    'id'              => $attribute->id,
                    'attribute_id'    => $attribute->attribute_id,
                    'attribute_name'  => $attribute->attribute->name ?? null,
                    'attribute_value' => $attribute->attribute_value,
                ];
            });
        
            $data['categories'] = collect($itinerary->categories)->map(function ($category) {
                return [
                    'id'            => $category->id,
                    'category_id'   => $category->category_id,
                    'category_name' => $category->category->name ?? null,
                ];
            });
        
            return $data;
        });
        

        return response()->json([
            'success'      => true,
            'data'         => $transformed->values(),
            'current_page' => (int) $page,
            'per_page'     => $perPage,
            'total'        => $allItems->count(),
        ], 200);
    }


    /**
     * Store a newly created Itinerary in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name'                  => 'required|string|max:255',
            'slug'                  => 'required|string|unique:itineraries,slug',
            'description'           => 'nullable|string',
            'featured_itinerary'    => 'boolean',
            'private_itinerary'     => 'boolean',
            'locations'             => 'nullable|array',
            'information'           => 'nullable|array',
            'schedules'             => 'nullable|array',
            'activities'            => 'nullable|array',
            'transfers'             => 'nullable|array',
            'pricing'               => 'nullable|array',
            'price_variations'      => 'nullable|array',
            'blackout_dates'        => 'nullable|array',
            'inclusions_exclusions' => 'nullable|array',
            'media_gallery'         => 'nullable|array',
            'faqs'                  => 'nullable|array',
            'seo'                   => 'nullable|array',
            'categories'            => 'nullable|array',
            'attributes'            => 'nullable|array',
            'tags'                  => 'nullable|array',
            'addons'                => 'nullable|array',
            'availability'          => 'nullable|array',
        ];
    
        $request->validate($rules);
    
        try {
            DB::beginTransaction();
    
            $itinerary = Itinerary::create([
                'name'               => $request->name,
                'slug'               => $request->slug,
                'description'        => $request->description ?? null,
                'featured_itinerary' => $request->boolean('featured_itinerary'),
                'private_itinerary'  => $request->boolean('private_itinerary'),
            ]);
    
            // === Information ===
            if ($request->has('information')) {
                foreach ($request->information as $info) {
                    ItineraryInformation::create([
                        'itinerary_id'  => $itinerary->id,
                        'section_title' => $info['section_title'] ?? '',
                        'content'       => $info['content'] ?? '',
                    ]);
                }
            }
    
            if ($request->has('locations')) {
                foreach ($request->locations as $cityId) {
                    ItineraryLocation::create([
                        'itinerary_id' => $itinerary->id,
                        'city_id'      => $cityId,
                    ]);
                }
            }
    
            // === Schedules ===
            $scheduleMap = [];
            if ($request->has('schedules')) {
                foreach ($request->schedules as $schedule) {
                    $record = ItinerarySchedule::create([
                        'itinerary_id' => $itinerary->id,
                        'day'          => $schedule['day'],
                    ]);
                    $scheduleMap[$schedule['day']] = $record->id;
                }
            }
    
            // === Transfers ===
            if ($request->has('transfers')) {
                foreach ($request->transfers as $transfer) {
                    $scheduleId = $scheduleMap[$transfer['day']] ?? null;
                    if ($scheduleId) {
                        ItineraryTransfer::create([
                            'schedule_id'          => $scheduleId,
                            'transfer_id'          => $transfer['transfer_id'],
                            'start_time'           => $transfer['start_time'],
                            'end_time'             => $transfer['end_time'],
                            'notes'                => $transfer['notes'],
                            'price'                => $transfer['price'],
                            'included'             => $transfer['included'],
                            'pickup_location'      => $transfer['pickup_location'] ?? null,
                            'dropoff_location'     => $transfer['dropoff_location'] ?? null,
                            'pax'                  => $transfer['pax'] ?? null,
                        ]);
                    }
                }
            }
    
            // === Activities ===
            if ($request->has('activities')) {
                foreach ($request->activities as $activity) {
                    $scheduleId = $scheduleMap[$activity['day']] ?? null;
                    if ($scheduleId) {
                        ItineraryActivity::create([
                            'schedule_id'          => $scheduleId,
                            'activity_id'          => $activity['activity_id'],
                            'start_time'           => $activity['start_time'],
                            'end_time'             => $activity['end_time'],
                            'notes'                => $activity['notes'],
                            'price'                => $activity['price'],
                            'included'             => $activity['included'],
                        ]);
                    }
                }
            }
    
            // === Pricing ===
            if ($request->has('pricing')) {
                $basePricing = ItineraryBasePricing::create([
                    'itinerary_id' => $itinerary->id,
                    'currency'     => $request->pricing['currency'],
                    'availability' => $request->pricing['availability'],
                    'start_date'   => $request->pricing['start_date'],
                    'end_date'     => $request->pricing['end_date'],
                ]);
    
                if ($request->has('price_variations')) {
                    foreach ($request->price_variations as $variation) {
                        ItineraryPriceVariation::create([
                            'base_pricing_id' => $basePricing->id,
                            'name'            => $variation['name'],
                            'regular_price'   => $variation['regular_price'],
                            'sale_price'      => $variation['sale_price'],
                            'max_guests'      => $variation['max_guests'],
                            'description'     => $variation['description'],
                        ]);
                    }
                }
    
                if ($request->has('blackout_dates')) {
                    foreach ($request->blackout_dates as $date) {
                        ItineraryBlackoutDate::create([
                            'base_pricing_id' => $basePricing->id,
                            'date'            => $date['date'],
                            'reason'          => $date['reason'],
                        ]);
                    }
                }
            }
    
            // === Inclusions/Exclusions ===
            if ($request->has('inclusions_exclusions')) {
                foreach ($request->inclusions_exclusions as $ie) {
                    ItineraryInclusionExclusion::create([
                        'itinerary_id'    => $itinerary->id,
                        'type'            => $ie['type'],
                        'title'           => $ie['title'],
                        'description'     => $ie['description'],
                        'included'        => $ie['included'],
                    ]);
                }
            }
    
            // === Media Gallery ===
            if ($request->has('media_gallery')) {
                foreach ($request->media_gallery as $media) {
                    ItineraryMediaGallery::create([
                        'itinerary_id' => $itinerary->id,
                        'media_id'     => $media['media_id'],
                    ]);
                }
            }
    
            // === FAQs ===
            if ($request->has('faqs')) {
                foreach ($request->faqs as $faq) {
                    ItineraryFaq::create([
                        'itinerary_id'    => $itinerary->id,
                        'question_number' => $faq['question_number'] ?? null,
                        'question'        => $faq['question'],
                        'answer'          => $faq['answer'],
                    ]);
                }
            }
    
            // === SEO ===
            if ($request->has('seo')) {
                ItinerarySeo::create([
                    'itinerary_id'     => $itinerary->id,
                    'meta_title'       => $request->seo['meta_title'],
                    'meta_description' => $request->seo['meta_description'],
                    'keywords'         => $request->seo['keywords'],
                    'og_image_url'     => $request->seo['og_image_url'],
                    'canonical_url'    => $request->seo['canonical_url'],
                    'schema_type'      => $request->seo['schema_type'],
                    'schema_data'      => is_array($request->seo['schema_data']) 
                        ? json_encode($request->seo['schema_data']) 
                        : $request->seo['schema_data'],
                ]);
            }
    
            // === Categories ===
            if ($request->has('categories')) {
                foreach ($request->categories as $category_id) {
                    ItineraryCategory::create([
                        'itinerary_id' => $itinerary->id,
                        'category_id'  => $category_id,
                    ]);
                }
            }

            // Itinerary Addons
            if ($request->has('addons')) {
                foreach ($request->addons as $addon_id) {
                    ItineraryAddon::create([
                        'itinerary_id' => $itinerary->id,
                        'addon_id'    => $addon_id,
                    ]);
                }
            }

            if ($request->has('attributes')) {
            
                foreach ($request->input('attributes') as $attribute) {
                    ItineraryAttribute::create([
                        'itinerary_id'    => $itinerary->id,
                        'attribute_id'    => $attribute['attribute_id'],
                        'attribute_value' => $attribute['attribute_value'],
                    ]);
                }
            }
    
            // === Tags ===
            if ($request->has('tags')) {
                foreach ($request->tags as $tag_id) {
                    ItineraryTag::create([
                        'itinerary_id' => $itinerary->id,
                        'tag_id'       => $tag_id,
                    ]);
                }
            }
    
            // === Availability ===
            if ($request->has('availability')) {
                ItineraryAvailability::create([
                    'itinerary_id'             => $itinerary->id,
                    'date_based_itinerary'     => $request->availability['date_based_itinerary'],
                    'start_date'               => $request->availability['start_date'] ?? null,
                    'end_date'                 => $request->availability['end_date'] ?? null,
                    'quantity_based_itinerary' => $request->availability['quantity_based_itinerary'],
                    'max_quantity'             => $request->availability['max_quantity'] ?? null,
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message'   => 'Itinerary created successfully',
                'itinerary' => $itinerary
            ], 201);
    
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'   => 'Something went wrong',
                'details' => $e->getMessage(),
            ], 500);
        }
    }     
    

    /**
     * Display the specified Itinerary.
    */
    public function show(string $id)
    {
        // $itinerary = Itinerary::find($id);
        
        $itinerary = Itinerary::with([
            'locations.city',
            'categories.category',
            'attributes.attribute',
            'tags.tag',
            'schedules.transfers.transfer.mediaGallery.media',
            'schedules.activities.activity.mediaGallery.media',
            // 'basePricing.variations',
            'inclusionsExclusions',
            'mediaGallery.media',
            'availability', 'addons.addon',
            'seo',
        ])->find($id);
        
        if (!$itinerary) {
            return response()->json(['message' => 'Itinerary not found'], 404);
        }


        // Transform response
        $itineraryData = $itinerary->toArray();
    

        // Separate base_pricing
        $basePricing = optional($itinerary->basePricing)->only([
            'id', 'currency', 'availability', 'start_date', 'end_date'
        ]);

        // Separate variations
        $basePricingVariations = collect($itinerary->basePricing->variations ?? [])->map(function ($variation) {
            return [
                'id'                => $variation->id,
                'base_pricing_id'   => $variation->base_pricing_id,
                'name'              => $variation->name,
                'regular_price'     => $variation->regular_price,
                'sale_price'        => $variation->sale_price,
                'max_guests'        => $variation->max_guests,
                'description'       => $variation->description,
            ];
        })->values();

        $blackoutDates = collect($itinerary->basePricing->blackoutDates ?? [])->map(function ($blackoutDate) {
            return [
                'id'                => $blackoutDate->id,
                'base_pricing_id'   => $blackoutDate->base_pricing_id,
                'date'              => $blackoutDate->date,
                'reason'            => $blackoutDate->reason,
            ];
        })->values();

        $itineraryData['base_pricing']       = $basePricing;
        $itineraryData['price_variations']   = $basePricingVariations;
        $itineraryData['blackout_dates']     = $blackoutDates;


        // Schedules flat (only day)
        $itineraryData['schedules'] = collect($itinerary->schedules)->map(function ($schedule) {
            return [
                'id'  => $schedule->id,
                'day' => $schedule->day,
            ];
        });

        // Flatten activities with day
        $itineraryData['activities'] = collect($itinerary->schedules)->flatMap(function ($schedule) {
            return collect($schedule->activities)->map(function ($activity) use ($schedule) {
                $mediaItems = collect($activity->activity->mediaGallery ?? [])->map(function ($media) {
                    return [
                        'name'      => $media->media->name ?? null,
                        'alt_text'  => $media->media->alt_text ?? null,
                        'url'       => $media->media->url ?? null,
                    ];
                })->filter(fn($item) => $item['url'])->values();
                return [

                    'id'            => $activity->id,
                    'activity_id'   => $activity->activity_id,
                    'activity_name' => $activity->activity->name ?? null,
                    'media_url'     => $mediaItems,
                    'day'           => $schedule->day,
                    'start_time'    => $activity->start_time,
                    'end_time'      => $activity->end_time,
                    'notes'         => $activity->notes,
                    'price'         => (float) $activity->price,
                    'included'      => $activity->included,
                ];
            });
        })->values();

        // Flatten transfers with day
        $itineraryData['transfers'] = collect($itinerary->schedules)->flatMap(function ($schedule) {
            return collect($schedule->transfers)->map(function ($transfer) use ($schedule) {
                $mediaItems = collect($transfer->transfer->mediaGallery ?? [])->map(function ($media) {
                    return [
                        'name'      => $media->media->name ?? null,
                        'alt_text'  => $media->media->alt_text ?? null,
                        'url'       => $media->media->url ?? null,
                    ];
                })->filter(fn($item) => $item['url'])->values(); // null url वाले हटा दिए जाएं
                return [

                    "id"                => $transfer->id,
                    'transfer_id'       => $transfer->transfer_id,
                    'transfer_name'     => $transfer->transfer->name ?? null,
                    'media_url'         => $mediaItems,
                    'day'               => $schedule->day,
                    'start_time'        => $transfer->start_time,
                    'end_time'          => $transfer->end_time,
                    'notes'             => $transfer->notes,
                    'price'             => (float) $transfer->price,
                    'included'          => $transfer->included,
                    'pickup_location'   => $transfer->pickup_location,
                    'dropoff_location'  => $transfer->dropoff_location,
                    'pax'               => $transfer->pax,
                ];
            });
        })->values();

        // Replace location city object with just `city_name`
        $itineraryData['locations'] = collect($itinerary->locations)->map(function ($location) {
            return [
                'id'           => $location->id,
                'itinerary_id' => $location->itinerary_id,
                'city_id'      => $location->city_id,
                'city_name'    => $location->city->name ?? null,
            ];
        });

        $itineraryData['addons'] = collect($itinerary->addons)->map(function ($addon) {
            return [
                'id'                      => $addon->id,
                'addon_id'                => $addon->addon_id,
                'addon_name'              => $addon->addon->name ?? null,
                'addon_type'              => $addon->addon->type ?? null,
                'addon_description'       => $addon->addon->description ?? null,
                'addon_price'             => $addon->addon->price ?? null,
                'addon_sale_price'        => $addon->addon->sale_price ?? null,
                'addon_price_calculation' => $addon->addon->price_calculation ?? null,
                'addon_active_status'     => $addon->addon->active_status ?? null,
            ];
        });

        // Replace mediaobject with just `media data`
        $itineraryData['media_gallery'] = collect($itinerary->mediaGallery)->map(function ($media) {
            return [
                'id' => $media->id,
                'itinerary_id'  => $media->itinerary_id,
                'media_id'      => $media->media_id,
                'name'          => $media->media->name,
                'alt_text'      => $media->media->alt_text,
                'url'           => $media->media->url ?? null,
            ];
        });
    
        // Replace attributes with just `attribute_name`
        $itineraryData['attributes'] = collect($itinerary->attributes)->map(function ($attribute) {
            return [
                'id' => $attribute->id,
                'attribute_id'    => $attribute->attribute_id,
                'attribute_name'  => $attribute->attribute->name ?? null,
                'attribute_value' => $attribute->attribute_value,
            ];
        });
    
        // Replace categories with just `category_name`
        $itineraryData['categories'] = collect($itinerary->categories)->map(function ($category) {
            return [
                'id' => $category->id,
                'category_id'   => $category->category_id,
                'category_name' => $category->category->name ?? null,
            ];
        });
        $itineraryData['tags'] = collect($itinerary->tags)->map(function ($tag) {
            return [
                'id'       => $tag->id,
                'tag_id'   => $tag->tag_id,
                'tag_name' => $tag->tag->name ?? null,
            ];
        });

        return response()->json($itineraryData);
    }

    /**
     * Update the specified Itinerary in storage.
     */
    public function update(Request $request, $id)
    {
        $itinerary = Itinerary::findOrFail($id);
    
        $rules = [
            'name'                  => 'sometimes|string|max:255',
            'slug'                  => 'sometimes|string|unique:itineraries,slug,' . $itinerary->id,
            'description'           => 'nullable|string',
            'featured_itinerary'    => 'boolean',
            'private_itinerary'     => 'boolean',
            'locations'             => 'nullable|array',
            'information'           => 'nullable|array',
            'schedules'             => 'nullable|array',
            'activities'            => 'nullable|array',
            'transfers'             => 'nullable|array',
            'itineraries'           => 'nullable|array',
            'pricing'               => 'nullable|array',
            'price_variations'      => 'nullable|array',
            'blackout_dates'        => 'nullable|array',
            'inclusions_exclusions' => 'nullable|array',
            'media_gallery'         => 'nullable|array',
            'faqs'                  => 'nullable|array',
            'seo'                   => 'nullable|array',
            'categories'            => 'nullable|array',
            'attributes'            => 'nullable|array',
            'tags'                  => 'nullable|array',
            'addons'                => 'nullable|array',
            'availability'          => 'nullable|array',
        ];
    
        $request->validate($rules);
    
        try {
            DB::beginTransaction();
    
            $itinerary->fill($request->only([
                'name', 'slug', 'description', 'featured_itinerary', 'private_itinerary'
            ]));
            $itinerary->save();
    
            $scheduleMap = [];

            // $updateOrCreateRelation = function ($relationName, $data, $extra = []) use ($itinerary) {
            //     $relation = $itinerary->$relationName();
            //     $modelClass = get_class($relation->getRelated());
            //     $itineraryKey = $relation->getForeignKeyName(); // e.g., itinerary_id
            
            //     foreach ($data as $item) {
            //         $attributes = array_merge($item, $extra);
            //         if (!empty($item['id'])) {
            //             $model = $modelClass::find($item['id']);
            //             if ($model) {
            //                 $model->fill($attributes)->save();
            //             }
            //         } else {
            //             $attributes[$itineraryKey] = $itinerary->id;
            //             $relation->create($attributes);
            //         }
            //     }
            // };

            $updateOrCreateRelation = function ($relationName, $data, $extra = []) use ($itinerary) {

                $relation      = $itinerary->$relationName();
                $modelClass    = get_class($relation->getRelated());
                $itineraryKey  = $relation->getForeignKeyName();
            
                // 🔥 NEW: existing & incoming IDs
                $existingIds = $relation->pluck('id')->toArray();
                $incomingIds = collect($data)->pluck('id')->filter()->toArray();
            
                // 🔥 NEW: delete missing records
                $deleteIds = array_diff($existingIds, $incomingIds);
                if (!empty($deleteIds)) {
                    $relation->whereIn('id', $deleteIds)->delete();
                }
            
                // 🔁 update / create (same as before)
                foreach ($data as $item) {
            
                    $attributes = array_merge($item, $extra);
            
                    if (!empty($item['id'])) {
                        $model = $modelClass::find($item['id']);
                        if ($model) {
                            $model->fill($attributes)->save();
                        }
                    } else {
                        $attributes[$itineraryKey] = $itinerary->id;
                        $relation->create($attributes);
                    }
                }
            };            
            
    
            // foreach (['information', 'locations', 'faqs', 'inclusionsExclusions', 'mediaGallery'] as $relation) {
            foreach (['information', 'faqs', 'inclusionsExclusions', 'mediaGallery'] as $relation) {
                if ($request->has(Str::snake($relation))) {
                    $updateOrCreateRelation($relation, $request->{Str::snake($relation)});
                }
            }
    
            if ($request->has('schedules')) {
                $updateOrCreateRelation('schedules', $request->schedules);
                foreach ($itinerary->schedules as $schedule) {
                    $scheduleMap[$schedule->day] = $schedule->id;
                }
            }

            $scheduleMap = $itinerary->schedules()->pluck('id', 'day')->toArray();

            $updateOrCreateSimple = function ($modelClass, $data, $scheduleMap = []) {
                foreach ($data as $item) {
                    if (!empty($item['id'])) {
                        $model = $modelClass::find($item['id']);
                        if ($model) {
                            if (isset($item['day']) && empty($item['schedule_id'])) {
                                $scheduleId = $scheduleMap[$item['day']] ?? null;
                                if ($scheduleId) {
                                    $item['schedule_id'] = $scheduleId;
                                }
                                unset($item['day']);
                            }

                            $model->fill($item);
                            $model->save();
                        }
                    }
                }
            };
   
            
            
            if ($request->has('activities')) {
                $updateOrCreateSimple(\App\Models\ItineraryActivity::class, $request->activities, $scheduleMap);

            }
            
            if ($request->has('transfers')) {
                $updateOrCreateSimple(\App\Models\ItineraryTransfer::class, $request->transfers, $scheduleMap);

            }

            $pricing = $itinerary->basePricing()->first();

            // If pricing is present in request, create or update it
            if ($request->has('pricing')) {
                $pricing = $itinerary->basePricing()->firstOrCreate([]);
                $pricing->fill($request->pricing)->save();
            }

            $updateOrCreateChild = function ($relation, $data, $modelClass, $foreignKey) use ($pricing) {
                foreach ($data as $item) {
                    if (!empty($item['id'])) {
                        $model = $modelClass::find($item['id']);
                        if ($model) {
                            $model->fill($item);
                            $model->save();
                        }
                    } else {
                        $item[$foreignKey] = $pricing->id;
                        $modelClass::create($item);
                    }
                }
            };
            
            if ($request->has('price_variations')) {
                $updateOrCreateChild('priceVariations', $request->price_variations, \App\Models\ItineraryPriceVariation::class, 'base_pricing_id');
            }
            
            if ($request->has('blackout_dates')) {
                $updateOrCreateChild('blackoutDates', $request->blackout_dates, \App\Models\ItineraryBlackoutDate::class, 'base_pricing_id');
            }

            // Handle locations [1, 2, 3]
            if ($request->has('locations')) {
                $itinerary->locations()->delete();
                foreach ($request->locations as $locationId) {
                    $itinerary->locations()->create([
                        'city_id' => $locationId
                    ]);
                }
            }

            // Handle categories [1, 2, 3]
            if ($request->has('categories')) {
                $itinerary->categories()->delete();
                foreach ($request->categories as $categoryId) {
                    $itinerary->categories()->create([
                        'category_id' => $categoryId
                    ]);
                }
            }

            if ($request->has('addons')) {
                $itinerary->addons()->delete();
                foreach ($request->addons as $addonId) {
                    $itinerary->addons()->create([
                        'addon_id' => $addonId
                    ]);
                }
            }

            // Handle tags [1, 2, 3]
            if ($request->has('tags')) {
                $itinerary->tags()->delete();
                foreach ($request->tags as $tagId) {
                    $itinerary->tags()->create([
                        'tag_id' => $tagId
                    ]);
                }
            }

            if ($request->has('attributes')) {
                $itinerary->attributes()->delete();
            
                $attributes = collect($request->input('attributes'))->map(function ($attr) use ($itinerary) {
                    return array_merge($attr, ['activity_id' => $itinerary->id]);
                })->toArray();
            
                $itinerary->attributes()->createMany($attributes);
            } 
    
            if ($request->has('availability')) {
                $itinerary->availability()->updateOrCreate([], $request->availability);
            }
    
            if ($request->has('seo')) {
                $seoData = $request->seo;
                if (isset($seoData['schema_data']) && is_array($seoData['schema_data'])) {
                    $seoData['schema_data'] = json_encode($seoData['schema_data']);
                }
                $itinerary->seo()->updateOrCreate([], $seoData);
            }
    
            DB::commit();
    
            return response()->json([
                'message'   => 'itinerary updated successfully',
                'itinerary' => $itinerary->fresh()
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error'   => 'Something went wrong',
                'details' => $e->getMessage(),
            ], 500);
        }
    }    

    /**
     * Remove the specified Itinerary from storage.
    */
    public function destroy(string $id)
    {
        $itinerary = Itinerary::find($id);
        
        if (!$itinerary) {
            return response()->json(['message' => 'Itinerary not found'], 404);
        }
        
        $itinerary->delete();

        return response()->json(['message' => 'Itinerary deleted successfully']);
    }

    /**
     * Remove the specified itinerary specific fields from storage.
     */
    public function partialDelete(Request $request, string $id)
    {
        $itinerary = Itinerary::with('schedules.activities', 'schedules.transfers', 'basePricing.variations', 'basePricing.blackoutDates')->find($id);

        if (!$itinerary) {
            return response()->json(['message' => 'Itinerary not found'], 404);
        }

        // Delete selected activities via schedules
        if ($request->has('deleted_activity_ids')) {
            foreach ($itinerary->schedules as $schedule) {
                $schedule->activities()
                    ->whereIn('id', $request->deleted_activity_ids)
                    ->delete();
            }
        }

        // Delete selected transfers via schedules
        if ($request->has('deleted_transfer_ids')) {
            foreach ($itinerary->schedules as $schedule) {
                $schedule->transfers()
                    ->whereIn('id', $request->deleted_transfer_ids)
                    ->delete();
            }
        }

        // Delete selected schedules directly
        if ($request->has('deleted_schedule_ids')) {
            $itinerary->schedules()
                ->whereIn('id', $request->deleted_schedule_ids)
                ->delete();
        }

        // Delete selected price variation directly
        if ($request->has('deleted_price_variation_ids') && $itinerary->basePricing) {
            $itinerary->basePricing->variations()
                ->whereIn('id', $request->deleted_price_variation_ids)
                ->delete();
        }

        // Delete selected blackout dates directly
        if ($request->has('deleted_blackout_date_ids') && $itinerary->basePricing) {
            $itinerary->basePricing->blackoutDates()
                ->whereIn('id', $request->deleted_blackout_date_ids)
                ->delete();
        }

        // Delete selected inclusion exclusion directly
        if ($request->has('deleted_inclusion_exclusion_ids')) {
            $itinerary->inclusionsExclusions()
                ->whereIn('id', $request->deleted_inclusion_exclusion_ids)
                ->delete();
        }

        return response()->json(['message' => 'Selected items deleted successfully']);
    }

    /**
     * Remove the bulk itineraries from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'itinerary_ids' => 'required|array',
            'itinerary_ids.*' => 'integer|exists:itineraries,id',
        ]);

        DB::beginTransaction();
        try {
            // This will automatically cascade delete related rows if foreign keys are set correctly
            Itinerary::whereIn('id', $validated['itinerary_ids'])->delete();

            DB::commit();

            return response()->json([
                'message' => 'Selected itineraries deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to delete selected itineraries.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}
