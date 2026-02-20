<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\PackageInformation;
use App\Models\PackageLocation;
use App\Models\PackageSchedule;
use App\Models\PackageTransfer;
use App\Models\PackageActivity;
use App\Models\PackageItinerary;
use App\Models\PackageBasePricing;
use App\Models\PackagePriceVariation;
use App\Models\PackageBlackoutDate;
use App\Models\PackageInclusionExclusion;
use App\Models\PackageMediaGallery;
use App\Models\PackageCategory;
use App\Models\PackageAttribute;
use App\Models\PackageTag;
use App\Models\PackageFaq;
use App\Models\PackageSeo;
use App\Models\PackageAvailability;
use App\Models\PackageAddon;
use App\Models\Category;
use App\Models\Attribute;
use App\Models\Tag;
use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Validator;


class PackageController extends Controller
{

    /**
     * Display a listing of the itineraries.
    */
    public function index(Request $request)
    {
        $perPage        = 3; 
        $page           = $request->get('page', 1); 

        $search         = $request->get('search'); // Search by package name
        $categorySlug   = $request->get('category');
        $difficulty     = $request->get('difficulty_level');
        $duration       = $request->get('duration');
        $ageGroup       = $request->get('age_restriction');
        $season         = $request->get('season');
        $minPrice       = $request->get('min_price', 0);
        $maxPrice       = $request->get('max_price');
        $sortBy         = $request->get('sort_by', 'id_desc'); // Default: Newest First

        $category       = $categorySlug ? Category::where('slug', $categorySlug)->first() : null;
        $categoryId     = $category ? $category->id : null;

        $difficultyAttr = Attribute::where('slug', 'difficulty-level')->first();
        $durationAttr   = Attribute::where('slug', 'duration')->first();
        $ageGroupAttr   = Attribute::where('slug', 'age-restriction')->first();

        $query = Package::query()
            ->select('packages.*')  
            ->join('package_base_pricing', 'package_base_pricing.package_id', '=', 'packages.id') 
            ->join('package_price_variations', 'package_price_variations.base_pricing_id', '=', 'package_base_pricing.id')
            ->with([
                'categories.category', 
                'locations.city', 
                'basePricing.variations', 
                'attributes.attribute:id,name',
                'mediaGallery.media', 'addons.addon'
            ])

            ->when($search, fn($query) =>
                $query->where('packages.name', 'like', "%{$search}%")
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
                $query->orderBy('package_price_variations.sale_price', 'asc');
                break;
            case 'price_desc':
                $query->orderBy('package_price_variations.sale_price', 'desc');
                break;
            case 'name_asc':
                $query->orderBy('packages.name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('packages.name', 'desc');
                break;
            case 'id_asc':
                $query->orderBy('packages.id', 'asc');
                break;
            case 'id_desc':
                $query->orderBy('packages.id', 'desc');
                break;
            case 'featured':
                $query->orderByRaw('packages.featured_itinerary DESC');
                break;
            default:
                $query->orderBy('packages.id', 'desc');
                break;
        }

        $allItems = $query->get();
        $paginatedItems = $allItems->forPage($page, $perPage);

        $transformed = $paginatedItems->map(function ($package) {
            
            $data = $package->toArray(); // keep all original fields
        
            // Replace transformed fields for addons
            $data['addons'] = collect($package->addons)->map(function ($addon) {
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
            $data['locations'] = collect($package->locations)->map(function ($location) {
                return [
                    'id'         => $location->id,
                    'city_id'    => $location->city_id,
                    'city_name'  => $location->city->name ?? null,
                ];
            });
        
            $data['media_gallery'] = collect($package->mediaGallery)->map(function ($media) {
                return [
                    'id'         => $media->id,
                    'media_id'   => $media->media_id,
                    'name'       => $media->media->name ?? null,
                    'alt_text'   => $media->media->alt_text ?? null,
                    'url'        => $media->media->url ?? null,
                ];
            });
        
            $data['attributes'] = collect($package->attributes)->map(function ($attribute) {
                return [
                    'id'              => $attribute->id,
                    'attribute_id'    => $attribute->attribute_id,
                    'attribute_name'  => $attribute->attribute->name ?? null,
                    'attribute_value' => $attribute->attribute_value,
                ];
            });
        
            $data['categories'] = collect($package->categories)->map(function ($category) {
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
     * Store a newly created Package in storage.
     */
    public function store(Request $request)
    {
        $rules = [
            'name'                  => 'required|string|max:255',
            'slug'                  => 'required|string|unique:packages,slug',
            'description'           => 'nullable|string',
            'featured_package'      => 'boolean',
            'private_package'       => 'boolean',
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
    
            $package = Package::create([
                'name'             => $request->name,
                'slug'             => $request->slug,
                'description'      => $request->description ?? null,
                'featured_package' => $request->boolean('featured_package'),
                'private_package'  => $request->boolean('private_package'),
            ]);
    
            // === Information ===
            if ($request->has('information')) {
                foreach ($request->information as $info) {
                    PackageInformation::create([
                        'package_id'    => $package->id,
                        'section_title' => $info['section_title'] ?? '',
                        'content'       => $info['content'] ?? '',
                    ]);
                }
            }

            // === Locations ===
            if ($request->has('locations')) {
                foreach ($request->locations as $cityId) {
                    PackageLocation::create([
                        'package_id' => $package->id,
                        'city_id'    => $cityId,
                    ]);
                }
            }
    
            // === Schedules ===
            $scheduleMap = [];
            if ($request->has('schedules')) {
                foreach ($request->schedules as $schedule) {
                    $record = PackageSchedule::create([
                        'package_id' => $package->id,
                        'day'        => $schedule['day'],
                    ]);
                    $scheduleMap[$schedule['day']] = $record->id;
                }
            }
    
            // === Transfers ===
            if ($request->has('transfers')) {
                foreach ($request->transfers as $transfer) {
                    $scheduleId = $scheduleMap[$transfer['day']] ?? null;
                    if ($scheduleId) {
                        PackageTransfer::create([
                            'schedule_id'        => $scheduleId,
                            'transfer_id'        => $transfer['transfer_id'],
                            'start_time'         => $transfer['start_time'],
                            'end_time'           => $transfer['end_time'],
                            'notes'              => $transfer['notes'],
                            'price'              => $transfer['price'],
                            'included'           => $transfer['included'],
                            'pickup_location'    => $transfer['pickup_location'] ?? null,
                            'dropoff_location'   => $transfer['dropoff_location'] ?? null,
                            'pax'                => $transfer['pax'] ?? null,
                        ]);
                    }
                }
            }
    
            // === Activities ===
            if ($request->has('activities')) {
                foreach ($request->activities as $activity) {
                    $scheduleId = $scheduleMap[$activity['day']] ?? null;
                    if ($scheduleId) {
                        PackageActivity::create([
                            'schedule_id'        => $scheduleId,
                            'activity_id'        => $activity['activity_id'],
                            'start_time'         => $activity['start_time'],
                            'end_time'           => $activity['end_time'],
                            'notes'              => $activity['notes'],
                            'price'              => $activity['price'],
                            'included'           => $activity['included'],
                        ]);
                    }
                }
            }
    
            // === Itineraries ===
            if ($request->has('itineraries')) {
                foreach ($request->itineraries as $itinerary) {
                    $scheduleId = $scheduleMap[$itinerary['day']] ?? null;
                    if ($scheduleId) {
                        PackageItinerary::create([
                            'schedule_id'        => $scheduleId,
                            'itinerary_id'       => $itinerary['itinerary_id'],
                            'start_time'         => $itinerary['start_time'],
                            'end_time'           => $itinerary['end_time'],
                            'notes'              => $itinerary['notes'],
                            'price'              => $itinerary['price'],
                            'included'           => $itinerary['included'],
                            'pickup_location'    => $itinerary['pickup_location'] ?? null,
                            'dropoff_location'   => $itinerary['dropoff_location'] ?? null,
                            'pax'                => $itinerary['pax'] ?? null,
                        ]);
                    }
                }
            }
    
            // === Pricing ===
            if ($request->has('pricing')) {
                $basePricing = PackageBasePricing::create([
                    'package_id'   => $package->id,
                    'currency'     => $request->pricing['currency'],
                    'availability' => $request->pricing['availability'],
                    'start_date'   => $request->pricing['start_date'],
                    'end_date'     => $request->pricing['end_date'],
                ]);
    
                if ($request->has('price_variations')) {
                    foreach ($request->price_variations as $variation) {
                        PackagePriceVariation::create([
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
                        PackageBlackoutDate::create([
                            'base_pricing_id' => $basePricing->id,
                            'date'   => $date['date'],
                            'reason' => $date['reason'],
                        ]);
                    }
                }
            }
    
            // === Inclusions/Exclusions ===
            if ($request->has('inclusions_exclusions')) {
                foreach ($request->inclusions_exclusions as $ie) {
                    PackageInclusionExclusion::create([
                        'package_id'      => $package->id,
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
                    PackageMediaGallery::create([
                        'package_id' => $package->id,
                        'media_id'   => $media['media_id'],
                    ]);
                }
            }
    
            // === FAQs ===
            if ($request->has('faqs')) {
                foreach ($request->faqs as $faq) {
                    PackageFaq::create([
                        'package_id'      => $package->id,
                        // 'question_number' => $faq['question_number'] ?? null,
                        'question'        => $faq['question'],
                        'answer'          => $faq['answer'],
                    ]);
                }
            }
    
            // === SEO ===
            if ($request->has('seo')) {
                PackageSeo::create([
                    'package_id'       => $package->id,
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
                    PackageCategory::create([
                        'package_id'  => $package->id,
                        'category_id' => $category_id,
                    ]);
                }
            }

            // Package Addons
            if ($request->has('addons')) {
                foreach ($request->addons as $addon_id) {
                    PackageAddon::create([
                        'package_id' => $package->id,
                        'addon_id'   => $addon_id,
                    ]);
                }
            }

            if ($request->has('attributes')) {
            
                foreach ($request->input('attributes') as $attribute) {
                    PackageAttribute::create([
                        'package_id'      => $package->id,
                        'attribute_id'    => $attribute['attribute_id'],
                        'attribute_value' => $attribute['attribute_value'],
                    ]);
                }
            }
    
            // === Tags ===
            if ($request->has('tags')) {
                foreach ($request->tags as $tag_id) {
                    PackageTag::create([
                        'package_id' => $package->id,
                        'tag_id'     => $tag_id,
                    ]);
                }
            }
    
            // === Availability ===
            if ($request->has('availability')) {
                PackageAvailability::create([
                    'package_id'             => $package->id,
                    'date_based_package'     => $request->availability['date_based_package'],
                    'start_date'             => $request->availability['start_date'] ?? null,
                    'end_date'               => $request->availability['end_date'] ?? null,
                    'quantity_based_package' => $request->availability['quantity_based_package'],
                    'max_quantity'           => $request->availability['max_quantity'] ?? null,
                ]);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Package created successfully',
                'package' => $package
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
     * Display the specified Package.
     */
    public function show(string $id)
    {
        // $package = Package::find($id);

        $package = Package::with([
            'locations.city',
            'categories.category',
            'attributes.attribute',
            'tags.tag',
            'information',
            'schedules.transfers.transfer.mediaGallery.media',
            'schedules.activities.activity.mediaGallery.media',
            'schedules.itineraries.itinerary.mediaGallery.media',
            // 'basePricing.variations',
            'inclusionsExclusions',
            'mediaGallery.media',
            'availability', 'addons.addon',
            'seo',
            'faqs',
        ])->find($id);
        
        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        // Transform response
        $packageData = $package->toArray();

        // Separate base_pricing
        $basePricing = optional($package->basePricing)->only([
            'id', 'currency', 'availability', 'start_date', 'end_date'
        ]);

        // Separate variations
        $basePricingVariations = collect($package->basePricing->variations ?? [])->map(function ($variation) {
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

        $blackoutDates = collect($package->basePricing->blackoutDates ?? [])->map(function ($blackoutDate) {
            return [
                'id'                => $blackoutDate->id,
                'base_pricing_id'   => $blackoutDate->base_pricing_id,
                'date'              => $blackoutDate->date,
                'reason'            => $blackoutDate->reason,
            ];
        })->values();

        $packageData['base_pricing']       = $basePricing;
        $packageData['price_variations']   = $basePricingVariations;
        $packageData['blackout_dates']     = $blackoutDates;


        // Schedules flat (only day)
        $packageData['schedules'] = collect($package->schedules)->map(function ($schedule) {
            return [
                'id'  => $schedule->id,
                'day' => $schedule->day,
            ];
        });

        // Flatten activities with day
        $packageData['activities'] = collect($package->schedules)->flatMap(function ($schedule) {
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
        $packageData['transfers'] = collect($package->schedules)->flatMap(function ($schedule) {
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

        // Flatten itineraries with day
        $packageData['itineraries'] = collect($package->schedules)->flatMap(function ($schedule) {
            return collect($schedule->itineraries)->map(function ($itinerary) use ($schedule) {
                $mediaItems = collect($itinerary->itinerary->mediaGallery ?? [])->map(function ($media) {
                    return [
                        'name'      => $media->media->name ?? null,
                        'alt_text'  => $media->media->alt_text ?? null,
                        'url'       => $media->media->url ?? null,
                    ];
                })->filter(fn($item) => $item['url'])->values();
                return [

                    'id'             => $itinerary->id,
                    'itinerary_id'   => $itinerary->itinerary_id,
                    'itinerary_name' => $itinerary->itinerary->name ?? null,
                    'media_url'      => $mediaItems,
                    'day'            => $schedule->day,
                    'start_time'     => $itinerary->start_time,
                    'end_time'       => $itinerary->end_time,
                    'notes'          => $itinerary->notes,
                    'price'          => (float) $itinerary->price,
                    'included'       => $itinerary->included,
                ];
            });
        })->values();
        
        // Replace location city object with just `city_name`
        $packageData['locations'] = collect($package->locations)->map(function ($location) {
            return [
                'id'         => $location->id,
                'package_id' => $location->package_id,
                'city_id'    => $location->city_id,
                'city_name'  => $location->city->name ?? null,
            ];
        });

        $packageData['addons'] = collect($package->addons)->map(function ($addon) {
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
        $packageData['media_gallery'] = collect($package->mediaGallery)->map(function ($media) {
            return [
                'id'            => $media->id,
                'package_id'  => $media->package_id,
                'media_id'      => $media->media_id,
                'name'          => $media->media->name,
                'alt_text'      => $media->media->alt_text,
                'url'           => $media->media->url ?? null,
            ];
        });

        // Replace attributes with just `attribute_name`
        $packageData['attributes'] = collect($package->attributes)->map(function ($attribute) {
            return [
                'id'              => $attribute->id,
                'attribute_id'    => $attribute->attribute_id,
                'attribute_name'  => $attribute->attribute->name ?? null,
                'attribute_value' => $attribute->attribute_value,
            ];
        });
    
        // Replace categories with just `category_name`
        $packageData['categories'] = collect($package->categories)->map(function ($category) {
            return [
                'id'            => $category->id,
                'category_id'   => $category->category_id,
                'category_name' => $category->category->name ?? null,
            ];
        });
        $packageData['tags'] = collect($package->tags)->map(function ($tag) {
            return [
                'id'       => $tag->id,
                'tag_id'   => $tag->tag_id,
                'tag_name' => $tag->tag->name ?? null,
            ];
        });

        return response()->json($packageData);
    }

    /**
     * Update the specified Package in storage.
     */
    public function update(Request $request, $id)
    {
        $package = Package::findOrFail($id);
    
        $rules = [
            'name'                  => 'sometimes|string|max:255',
            'slug'                  => 'sometimes|string|unique:packages,slug,' . $package->id,
            'description'           => 'nullable|string',
            'featured_package'      => 'boolean',
            'private_package'       => 'boolean',
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
    
            $package->fill($request->only([
                'name', 'slug', 'description', 'featured_package', 'private_package'
            ]));
            $package->save();
    
            $scheduleMap = [];

            // $updateOrCreateRelation = function ($relationName, $data, $extra = []) use ($package) {
            //     $relation = $package->$relationName();
            //     $modelClass = get_class($relation->getRelated());
            //     $packageKey = $relation->getForeignKeyName(); // e.g., package_id
            
            //     foreach ($data as $item) {
            //         $attributes = array_merge($item, $extra);
            //         if (!empty($item['id'])) {
            //             $model = $modelClass::find($item['id']);
            //             if ($model) {
            //                 $model->fill($attributes)->save();
            //             }
            //         } else {
            //             $attributes[$packageKey] = $package->id;
            //             $relation->create($attributes);
            //         }
            //     }
            // };

            $updateOrCreateRelation = function ($relationName, $data, $extra = []) use ($package) {

                $relation      = $package->$relationName();
                $modelClass    = get_class($relation->getRelated());
                $packageKey  = $relation->getForeignKeyName();
            
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
                        $attributes[$packageKey] = $package->id;
                        $relation->create($attributes);
                    }
                }
            }; 
    
            foreach (['information', 'faqs', 'inclusionsExclusions', 'mediaGallery'] as $relation) {
                if ($request->has(Str::snake($relation))) {
                    $updateOrCreateRelation($relation, $request->{Str::snake($relation)});
                }
            }
    
            if ($request->has('schedules')) {
                $updateOrCreateRelation('schedules', $request->schedules);
                foreach ($package->schedules as $schedule) {
                    $scheduleMap[$schedule->day] = $schedule->id;
                }
            }

            $scheduleMap = $package->schedules()->pluck('id', 'day')->toArray();

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
                $updateOrCreateSimple(\App\Models\PackageActivity::class, $request->activities, $scheduleMap);

            }
            
            if ($request->has('transfers')) {
                $updateOrCreateSimple(\App\Models\PackageTransfer::class, $request->transfers, $scheduleMap);

            }
            
            if ($request->has('itineraries')) {
                $updateOrCreateSimple(\App\Models\PackageItinerary::class, $request->itineraries, $scheduleMap);

            }

            $pricing = $package->basePricing()->first();

            // If pricing is present in request, create or update it
            if ($request->has('pricing')) {
                $pricing = $package->basePricing()->firstOrCreate([]);
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
                $updateOrCreateChild('priceVariations', $request->price_variations, \App\Models\PackagePriceVariation::class, 'base_pricing_id');
            }
            
            if ($request->has('blackout_dates')) {
                $updateOrCreateChild('blackoutDates', $request->blackout_dates, \App\Models\PackageBlackoutDate::class, 'base_pricing_id');
            }

            // Handle locations [1, 2, 3]
            if ($request->has('locations')) {
                $package->locations()->delete();
                foreach ($request->locations as $locationId) {
                    $package->locations()->create([
                        'city_id' => $locationId
                    ]);
                }
            }

            // Handle categories [1, 2, 3]
            if ($request->has('categories')) {
                $package->categories()->delete();
                foreach ($request->categories as $categoryId) {
                    $package->categories()->create([
                        'category_id' => $categoryId
                    ]);
                }
            }

            if ($request->has('addons')) {
                $package->addons()->delete();
                foreach ($request->addons as $addonId) {
                    $package->addons()->create([
                        'addon_id' => $addonId
                    ]);
                }
            }

            // Handle tags [1, 2, 3]
            if ($request->has('tags')) {
                $package->tags()->delete();
                foreach ($request->tags as $tagId) {
                    $package->tags()->create([
                        'tag_id' => $tagId
                    ]);
                }
            }

            // // Handle attributes with attribute_value
            // if ($request->has('attributes')) {
            //     $package->attributes()->delete(); // Delete existing attributes
            //     foreach ($request->attributes as $attribute) {
            //         // Create or update each attribute with its value
            //         $package->attributes()->create([
            //             'attribute_id'    => $attribute['attribute_id'],
            //             'attribute_value' => $attribute['attribute_value']
            //         ]);
            //     }
            // }
            if ($request->has('attributes')) {
                $package->attributes()->delete();
            
                $attributes = collect($request->input('attributes'))->map(function ($attr) use ($package) {
                    return array_merge($attr, ['activity_id' => $package->id]);
                })->toArray();
            
                $package->attributes()->createMany($attributes);
            } 
    
            if ($request->has('availability')) {
                $package->availability()->updateOrCreate([], $request->availability);
            }
    
            if ($request->has('seo')) {
                $seoData = $request->seo;
                if (isset($seoData['schema_data']) && is_array($seoData['schema_data'])) {
                    $seoData['schema_data'] = json_encode($seoData['schema_data']);
                }
                $package->seo()->updateOrCreate([], $seoData);
            }
    
            DB::commit();
    
            return response()->json([
                'message' => 'Package updated successfully',
                'package' => $package->fresh()
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
     * Remove the specified Package from storage.
     */
    public function destroy(string $id)
    {
        $package = Package::find($id);
        
        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }
        
        $package->delete();

        return response()->json(['message' => 'Package deleted successfully']);
    }

    /**
     * Remove the specified package specific fields from storage.
     */
    public function partialDelete(Request $request, string $id)
    {
        $package = Package::with('information', 'schedules.activities', 'schedules.transfers', 'basePricing.variations', 'basePricing.blackoutDates')->find($id);

        if (!$package) {
            return response()->json(['message' => 'Package not found'], 404);
        }

        // Delete selected information
        if ($request->has('deleted_information_ids')) {
            $package->information()
                ->whereIn('id', $request->deleted_information_ids)
                ->delete();
        }

        // Delete selected activities via schedules
        if ($request->has('deleted_activity_ids')) {
            foreach ($package->schedules as $schedule) {
                $schedule->activities()
                    ->whereIn('id', $request->deleted_activity_ids)
                    ->delete();
            }
        }

        // Delete selected transfers via schedules
        if ($request->has('deleted_transfer_ids')) {
            foreach ($package->schedules as $schedule) {
                $schedule->transfers()
                    ->whereIn('id', $request->deleted_transfer_ids)
                    ->delete();
            }
        }

        // Delete selected itineraries via schedules
        if ($request->has('deleted_itinerary_ids')) {
            foreach ($package->schedules as $schedule) {
                $schedule->itineraries()
                    ->whereIn('id', $request->deleted_itinerary_ids)
                    ->delete();
            }
        }

        // Delete selected schedules directly
        if ($request->has('deleted_schedule_ids')) {
            $package->schedules()
                ->whereIn('id', $request->deleted_schedule_ids)
                ->delete();
        }

        // Delete selected price variation directly
        if ($request->has('deleted_price_variation_ids') && $package->basePricing) {
            $package->basePricing->variations()
                ->whereIn('id', $request->deleted_price_variation_ids)
                ->delete();
        }

        // Delete selected blackout dates directly
        if ($request->has('deleted_blackout_date_ids') && $package->basePricing) {
            $package->basePricing->blackoutDates()
                ->whereIn('id', $request->deleted_blackout_date_ids)
                ->delete();
        }

        // Delete selected inclusion exclusion directly
        if ($request->has('deleted_inclusion_exclusion_ids')) {
            $package->inclusionsExclusions()
                ->whereIn('id', $request->deleted_inclusion_exclusion_ids)
                ->delete();
        }

        // Delete selected faq directly
        if ($request->has('deleted_faq_ids')) {
            $package->faqs()
                ->whereIn('id', $request->deleted_faq_ids)
                ->delete();
        }

        return response()->json(['message' => 'Selected items deleted successfully']);
    }

    /**
     * Remove the bulk packages from storage.
     */
    public function bulkDestroy(Request $request)
    {
        $validated = $request->validate([
            'package_ids' => 'required|array',
            'package_ids.*' => 'integer|exists:packages,id',
        ]);

        DB::beginTransaction();
        try {
            // This will automatically cascade delete related rows if foreign keys are set correctly
            Package::whereIn('id', $validated['package_ids'])->delete();

            DB::commit();

            return response()->json([
                'message' => 'Selected packages deleted successfully.'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to delete selected packages.',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
