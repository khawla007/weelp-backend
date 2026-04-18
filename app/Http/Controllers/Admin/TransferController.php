<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transfer;
use App\Models\TransferVendorRoute;
use App\Models\TransferPricingAvailability;
use App\Models\TransferSchedule;
use App\Models\TransferMediaGallery;
use App\Models\TransferSeo;
use App\Models\TransferAddon;
use App\Models\TransferRoute;
use App\Models\TransferZonePrice;
use App\Models\Place;

class TransferController extends Controller
{
    /**
     * Display a listing of the transfers.
     */
    public function index(Request $request)
    {
        $perPage = 3;
        $page = $request->get('page', 1);
        $sortBy = $request->get('sort_by', 'id_desc');
        $fetchAll = $request->boolean('all');
    
        $search = $request->get('search');
        $vehicleType = $request->get('vehicle_type');
        $capacity = $request->get('capacity');
        $minPrice = $request->get('min_price');
        $maxPrice = $request->get('max_price');
        $availabilityType = $request->get('availability_type');
        $availableDays = $request->get('available_days');
        $timeSlotStart = preg_match('/^\d{2}:\d{2}$/', $request->get('time_slot_start', '')) ? $request->get('time_slot_start') : null;
        $timeSlotEnd = preg_match('/^\d{2}:\d{2}$/', $request->get('time_slot_end', '')) ? $request->get('time_slot_end') : null;

        $query = Transfer::query()
            ->with([
                'vendorRoutes.route',
                'vendorRoutes.vendor.vehicles',
                'vendorRoutes.vendor.availabilityTimeSlots',
                'vendorRoutes.pickupPlace',
                'pricingAvailability.pricingTier',
                'pricingAvailability.availability',
                'mediaGallery.media',
                'schedule',
                'seo',
            ])
            // Search filter
            ->when($search, function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('slug', 'like', '%' . $search . '%')
                  ->orWhere('description', 'like', '%' . $search . '%');
            })
            // Vehicle type filter
            ->when($vehicleType, function ($q) use ($vehicleType) {
                $q->whereHas('vendorRoutes', function ($q1) use ($vehicleType) {
                    $q1->where(function ($q2) use ($vehicleType) {
                        // Case 1: is_vendor = true → vendor's vehicles filter
                        $q2->where('is_vendor', true)
                            ->whereHas('vendor.vehicles', function ($q3) use ($vehicleType) {
                                $q3->where('vehicle_type', $vehicleType);
                            });
                    })
                    ->orWhere(function ($q2) use ($vehicleType) {
                        // Case 2: is_vendor = false → filter directly on transfer_vendor_routes
                        $q2->where('is_vendor', false)
                            ->where('vehicle_type', $vehicleType);
                    });
                });
            })
            // Capacity filter (via transfer_schedules.maximum_passengers)
            ->when($capacity, function ($q) use ($capacity) {
                $q->whereHas('schedule', function ($q2) use ($capacity) {
                    $q2->where('maximum_passengers', '>=', (int) $capacity);
                });
            })
            // Price filter
            ->when($minPrice !== null || $maxPrice !== null, function ($q) use ($minPrice, $maxPrice) {
                $q->whereHas('pricingAvailability', function ($q1) use ($minPrice, $maxPrice) {
                    $q1->where(function ($q2) use ($minPrice, $maxPrice) {
                        // Vendor side
                        $q2->where('is_vendor', true)
                           ->whereHas('pricingTier', function ($q3) use ($minPrice, $maxPrice) {
                               if ($minPrice !== null && $maxPrice !== null) {
                                   $q3->whereBetween('transfer_price', [$minPrice, $maxPrice]);
                               } elseif ($minPrice !== null) {
                                   $q3->where('transfer_price', '>=', $minPrice);
                               } elseif ($maxPrice !== null) {
                                   $q3->where('transfer_price', '<=', $maxPrice);
                               }
                           });
                    })
                    ->orWhere(function ($q2) use ($minPrice, $maxPrice) {
                        // Transfer side
                        $q2->where('is_vendor', false);
                        if ($minPrice !== null && $maxPrice !== null) {
                            $q2->whereBetween('transfer_price', [$minPrice, $maxPrice]);
                        } elseif ($minPrice !== null) {
                            $q2->where('transfer_price', '>=', $minPrice);
                        } elseif ($maxPrice !== null) {
                            $q2->where('transfer_price', '<=', $maxPrice);
                        }
                    });
                });
            })                             
            // Availability type filter (via transfer_schedules)
            ->when($availabilityType, function ($q) use ($availabilityType) {
                $q->whereHas('schedule', function ($q2) use ($availabilityType) {
                    $q2->where('availability_type', $availabilityType);
                });
            })
            // Available days filter (for custom_schedule type)
            ->when($availableDays, function ($q) use ($availableDays) {
                $days = explode(',', $availableDays);
                $q->whereHas('schedule', function ($q2) use ($days) {
                    $q2->where('availability_type', 'custom_schedule');
                    foreach ($days as $day) {
                        $q2->where('available_days', 'like', '%' . trim($day) . '%');
                    }
                });
            })
            // Time slot filter (for custom_schedule type)
            ->when($timeSlotStart || $timeSlotEnd, function ($q) use ($timeSlotStart, $timeSlotEnd) {
                $q->whereHas('schedule', function ($q2) use ($timeSlotStart, $timeSlotEnd) {
                    $q2->where('availability_type', 'custom_schedule');
                    if ($timeSlotStart) {
                        $q2->where('time_slots', 'like', '%"start":"' . $timeSlotStart . '"%');
                    }
                    if ($timeSlotEnd) {
                        $q2->where('time_slots', 'like', '%"end":"' . $timeSlotEnd . '"%');
                    }
                });
            });
    
        // Sorting
        switch ($sortBy) {
            case 'price_asc':
                $query->with(['pricingAvailability.pricingTier' => function ($q) {
                    $q->orderBy('transfer_price', 'asc');
                }]);
                break;
            case 'price_desc':
                $query->with(['pricingAvailability.pricingTier' => function ($q) {
                    $q->orderBy('transfer_price', 'desc');
                }]);
                break;
            case 'name_asc':
                $query->orderBy('name', 'asc');
                break;
            case 'name_desc':
                $query->orderBy('name', 'desc');
                break;
            case 'id_asc':
                $query->orderBy('id', 'asc');
                break;
            default:
                $query->orderBy('id', 'desc');
                break;
        }
    
        if ($fetchAll) {
            $collection = $query->get();
        } else {
            $paginated = $query->paginate($perPage, ['*'], 'page', $page);
            $collection = $paginated->getCollection();
        }

        $transformed = $collection->map(function ($transfer) {
            $data = $transfer->toArray();

            $data['media_gallery'] = collect($transfer->mediaGallery)->map(function ($media) {
                return [
                    'id'         => $media->id,
                    'media_id'   => $media->media_id,
                    'name'       => $media->media->name ?? null,
                    'alt_text'   => $media->media->alt_text ?? null,
                    'url'        => $media->media->url ?? null,
                    'is_featured'=> $media->is_featured ?? false,
                ];
            });

            // Get featured image from media_gallery
            $featuredImage = $transfer->mediaGallery->firstWhere('is_featured', true);
            $data['feature_image'] = $featuredImage?->media->url ?? null;
            $data['is_featured'] = $featuredImage !== null;

            // Add vendor_routes with is_vendor flag (required by frontend for Edit link routing)
            $data['vendor_routes'] = [
                'is_vendor'        => $transfer->vendorRoutes?->is_vendor ?? false,
                'pickup_place_id'  => $transfer->vendorRoutes?->pickup_place_id,
                'dropoff_place_id' => $transfer->vendorRoutes?->dropoff_place_id,
                'pickup_city_id'   => $transfer->vendorRoutes?->pickupPlace?->city_id,
            ];

            // Tags and attributes not yet implemented for Transfers - return empty arrays
            $data['tags'] = [];
            $data['attributes'] = [];

            return $data;
        });
    
        if ($fetchAll) {
            return response()->json([
                'success' => true,
                'data'    => $transformed,
                'total'   => $transformed->count(),
            ]);
        }

        return response()->json([
            'success'      => true,
            'data'         => $transformed,
            'current_page' => $paginated->currentPage(),
            'per_page'     => $paginated->perPage(),
            'total'        => $paginated->total(),
        ]);
    }

    /**
     * Store a newly created transfers in storage.
     */
    public function store(Request $request)
    {
        // Base validation rules
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:transfers,slug',
            'description' => 'nullable|string',
            'transfer_type' => 'required|string',
            'is_vendor' => 'required|boolean',
    
            // Vendor related
            'vendor_id' => 'nullable|integer|exists:vendors,id',
            'route_id' => 'nullable|integer|exists:vendor_routes,id',

            // Admin route reference (new: zone-based pricing)
            'transfer_route_id' => 'nullable|integer|exists:transfer_routes,id',
    
            // Non-vendor location fields
            'pickup_location'   => 'nullable|string|max:255',
            'dropoff_location'  => 'nullable|string|max:255',
            'pickup_place_id'   => 'nullable|integer|exists:places,id',
            'dropoff_place_id'  => 'nullable|integer|exists:places,id',
            'vehicle_type'      => 'nullable|string|max:255',
            'inclusion'         => 'nullable|string',

            // Vendor pricing/availability
            'pricing_tier_id' => 'nullable|integer|exists:vendor_pricing_tiers,id',
            'availability_id' => 'nullable|integer|exists:vendor_availability_time_slots,id',
    
            // Non-vendor pricing fields
            'transfer_price'       => 'nullable|numeric',
            'currency'             => 'nullable|string|max:10',
            'price_type'           => 'nullable|string|max:255',
            'extra_luggage_charge' => 'nullable|numeric',
            'waiting_charge'       => 'nullable|numeric',

            // Schedule fields
            'availability_type' => 'nullable|in:always_available,specific_date,custom_schedule',
            'available_days'    => 'nullable|array',
            'available_days.*'  => 'string',
            'time_slots'        => 'nullable|array',
            'time_slots.*.start'=> 'required_with:time_slots|date_format:H:i',
            'time_slots.*.end'  => 'required_with:time_slots|date_format:H:i',
            'blackout_dates'    => 'nullable|array',
            'blackout_dates.*'  => 'date',
            'minimum_lead_time' => 'nullable|integer',
            'maximum_passengers'=> 'nullable|integer',
    
            // Media
            'media_gallery'     => 'nullable|array',
    
            // SEO
            'seo' => 'array',
            'seo.meta_title'       => 'nullable|string|max:255',
            'seo.meta_description' => 'nullable|string',
            'seo.keywords'         => 'nullable|string',
            'seo.og_image_url'     => 'nullable|string',
            'seo.canonical_url'    => 'nullable|string',
            'seo.schema_type'      => 'nullable|string',
            'seo.schema_data'      => 'nullable|array',

            // Addons
            'addons' => 'nullable|array',
            'addons.*' => 'integer|exists:addons,id',
        ]);
    
        // Conditional validations
        if ($validatedData['is_vendor']) {
            $request->validate([
                'vendor_id' => 'required|integer|exists:vendors,id',
                'route_id' => 'required|integer|exists:vendor_routes,id',
                'pricing_tier_id' => 'required|integer|exists:vendor_pricing_tiers,id',
                'availability_id' => 'required|integer|exists:vendor_availability_time_slots,id',
            ]);
        } else {
            $request->validate([
                'transfer_route_id'    => 'required|integer|exists:transfer_routes,id',
                'vehicle_type'         => 'required|string|max:255',
                'inclusion'            => 'required|string',
                'transfer_price'       => 'required|numeric',
                'currency'             => 'required|string|max:10',
                'price_type'           => 'required|string|max:255',
                'extra_luggage_charge' => 'required|numeric',
                'waiting_charge'       => 'required|numeric',
            ]);

            $route = TransferRoute::find($request->input('transfer_route_id'));
            if (! $route || $route->origin_type !== 'place' || $route->destination_type !== 'place') {
                return response()->json([
                    'message' => 'Selected route must have place endpoints for transfers.',
                    'errors'  => ['transfer_route_id' => ['Selected route must have place endpoints for transfers.']],
                ], 422);
            }
        }

        // Auto-resolve from transfer_route (non-vendor flow only)
        $resolved = $this->resolveFromRoute($validatedData);

        // Create Transfer
        $transfer = Transfer::create([
            'name' => $validatedData['name'],
            'slug' => $validatedData['slug'],
            'description' => $validatedData['description'] ?? null,
            'transfer_type' => $validatedData['transfer_type'],
            'transfer_route_id' => $validatedData['transfer_route_id'] ?? null,
        ]);
    
        // Create TransferVendorRoute
        TransferVendorRoute::create([
            'transfer_id'       => $transfer->id,
            'is_vendor'         => $validatedData['is_vendor'],
            'vendor_id'         => $validatedData['is_vendor'] ? $validatedData['vendor_id'] : null,
            'route_id'          => $validatedData['is_vendor'] ? $validatedData['route_id'] : null,
            'pickup_location'   => null,
            'dropoff_location'  => null,
            'pickup_place_id'   => !$validatedData['is_vendor'] ? ($resolved['pickup_place_id'] ?? null) : null,
            'dropoff_place_id'  => !$validatedData['is_vendor'] ? ($resolved['dropoff_place_id'] ?? null) : null,
            'vehicle_type'      => !$validatedData['is_vendor'] ? $validatedData['vehicle_type'] : null,
            'inclusion'         => !$validatedData['is_vendor'] ? $validatedData['inclusion'] : null,
        ]);

        // Create Pricing Availability
        TransferPricingAvailability::create([
            'transfer_id'          => $transfer->id,
            'is_vendor'            => $validatedData['is_vendor'],
            'pricing_tier_id'      => $validatedData['is_vendor'] ? $validatedData['pricing_tier_id'] : null,
            'availability_id'      => $validatedData['is_vendor'] ? $validatedData['availability_id'] : null,
            'transfer_price'       => !$validatedData['is_vendor'] ? ($validatedData['transfer_price'] ?? $resolved['base_price'] ?? null) : null,
            'currency'             => !$validatedData['is_vendor'] ? ($validatedData['currency'] ?? $resolved['currency'] ?? null) : null,
            'price_type'           => !$validatedData['is_vendor'] ? ($validatedData['price_type'] ?? null) : null,
            'extra_luggage_charge' => !$validatedData['is_vendor'] ? $validatedData['extra_luggage_charge'] : null,
            'waiting_charge'       => !$validatedData['is_vendor'] ? $validatedData['waiting_charge'] : null,
        ]);
    
        // Create Schedule
        TransferSchedule::create([
            'transfer_id'       => $transfer->id,
            'is_vendor'         => $validatedData['is_vendor'],
            'availability_type' => $validatedData['availability_type'] ?? 'null',
            // 'availability_type' => !empty($validatedData['availability_type']) ? implode(',', $validatedData['availability_type']) : null,
            'available_days'    => !empty($validatedData['available_days']) ? implode(',', $validatedData['available_days']) : null,
            'time_slots'        => !empty($validatedData['time_slots']) ? json_encode($validatedData['time_slots']) : null,
            'blackout_dates'    => !empty($validatedData['blackout_dates']) ? json_encode($validatedData['blackout_dates']) : null,
            'minimum_lead_time' => $validatedData['minimum_lead_time'] ?? null,
            'maximum_passengers'=> $validatedData['maximum_passengers'] ?? null,
        ]);
    
        // === Media Gallery ===
        if (!empty($validatedData['media_gallery'])) {
            $hasFeatured = false;
            foreach ($validatedData['media_gallery'] as $media) {
                // Skip null media_id
                if (!isset($media['media_id']) || $media['media_id'] === null) {
                    continue;
                }

                // Ensure only ONE featured
                $isFeatured = $media['is_featured'] ?? false;
                if ($isFeatured) {
                    if ($hasFeatured) {
                        $isFeatured = false;
                    } else {
                        $hasFeatured = true;
                    }
                }

                TransferMediaGallery::create([
                    'transfer_id' => $transfer->id,
                    'media_id'    => $media['media_id'],
                    'is_featured' => $isFeatured,
                ]);
            }
        }
    
        // Create SEO
        if (!empty($validatedData['seo'])) {
            TransferSeo::create([
                'transfer_id' => $transfer->id,
                'meta_title' => $validatedData['seo']['meta_title'] ?? '',
                'meta_description' => $validatedData['seo']['meta_description'] ?? '',
                'keywords' => $validatedData['seo']['keywords'] ?? '',
                'og_image_url' => $validatedData['seo']['og_image_url'] ?? null,
                'canonical_url' => $validatedData['seo']['canonical_url'] ?? null,
                'schema_type' => $validatedData['seo']['schema_type'] ?? null,
                // 'schema_data' => $validatedData['seo']['schema_data'] ?? null,
                'schema_data' => is_array($validatedData['seo']['schema_data'] ?? null)
                    ? json_encode($validatedData['seo']['schema_data'])
                    : ($validatedData['seo']['schema_data'] ?? null),
            ]);
        }

        // Create Addons
        if (!empty($validatedData['addons'])) {
            foreach ($validatedData['addons'] as $addonId) {
                TransferAddon::create([
                    'transfer_id' => $transfer->id,
                    'addon_id' => $addonId,
                ]);
            }
        }

        return response()->json([
            'message' => 'Transfer created successfully',
            'transfer' => $transfer,
        ]);
    }       

    /**
     * Display the specified transfers.
     */
    // public function show(string $id)
    // {
    //     $transfer = Transfer::with(['vendorRoutes', 'pricingAvailability', 'mediaGallery.media', 'schedule', 'seo'])->find($id);
    //     if (!$transfer) {
    //         return response()->json(['message' => 'Transfer not found'], 404);
    //     }
    //     return response()->json($transfer);
    // }

    public function show(string $id)
    {
        $transfer = Transfer::with([
            'vendorRoutes.pickupPlace',
            'vendorRoutes.dropoffPlace',
            'pricingAvailability',
            'mediaGallery.media',
            'schedule',
            'seo',
            'addons.addon'
        ])->find($id);
    
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }
    
        // Normalize schedule scalars/CSV to arrays.
        // time_slots + blackout_dates are already array-cast by the model; only available_days is a CSV string.
        if ($transfer->schedule && !empty($transfer->schedule->available_days) && is_string($transfer->schedule->available_days)) {
            $transfer->schedule->available_days = explode(',', $transfer->schedule->available_days);
        }

        // media_gallery ko transform karna
        if ($transfer->mediaGallery && $transfer->mediaGallery->count()) {
            $transfer->media_gallery = $transfer->mediaGallery->map(function ($gallery) {
                return [
                    'id' => $gallery->id,
                    'transfer_id' => $gallery->transfer_id,
                    'media_id' => $gallery->media_id,
                    'name' => $gallery->media->name ?? null,
                    'alt_text' => $gallery->media->alt_text ?? null,
                    'url' => $gallery->media->url ?? null,
                    'is_featured' => $gallery->is_featured ?? false,
                ];
            })->values();

            // Get featured image from media_gallery
            $featuredImage = $transfer->mediaGallery->firstWhere('is_featured', true);
            $transfer->feature_image = $featuredImage?->media->url ?? null;

            unset($transfer->mediaGallery); // nested relation hatane ke liye
        }

        // Transform addons
        if ($transfer->addons && $transfer->addons->count()) {
            $transfer->addons = $transfer->addons->map(function ($addon) {
                return [
                    'id' => $addon->addon->id ?? null,
                    'name' => $addon->addon->name ?? null,
                ];
            })->filter(function ($addon) {
                return $addon['id'] !== null;
            })->values();
        }

        // Add place names for vendor routes
        if ($transfer->vendorRoutes) {
            $transfer->vendorRoutes->pickup_place_name  = $transfer->vendorRoutes->pickupPlace?->name;
            $transfer->vendorRoutes->dropoff_place_name = $transfer->vendorRoutes->dropoffPlace?->name;
            $transfer->vendorRoutes->pickup_city_id     = $transfer->vendorRoutes->pickupPlace?->city_id;
            $transfer->vendorRoutes->dropoff_city_id    = $transfer->vendorRoutes->dropoffPlace?->city_id;
        }

        return response()->json($transfer);
    }    

    /**
     * Update the specified transfers in storage.
     */
    public function update(Request $request, $id)
    {
        // Find existing Transfer
        $transfer = Transfer::findOrFail($id);
    
        // Validate only the fields coming in request
        $validatedData = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'sometimes|required|string|unique:transfers,slug,' . $id,
            'description' => 'sometimes|nullable|string',
            'transfer_type' => 'sometimes|required|string',
            'is_vendor' => 'sometimes|required|boolean',
    
            // Vendor related
            'vendor_id' => 'sometimes|nullable|integer|exists:vendors,id',
            'route_id' => 'sometimes|nullable|integer|exists:vendor_routes,id',

            // Admin route reference (zone-based pricing)
            'transfer_route_id' => 'sometimes|nullable|integer|exists:transfer_routes,id',

            // Non-vendor location fields
            'pickup_location'   => 'sometimes|nullable|string|max:255',
            'dropoff_location'  => 'sometimes|nullable|string|max:255',
            'pickup_place_id'   => 'sometimes|nullable|integer|exists:places,id',
            'dropoff_place_id'  => 'sometimes|nullable|integer|exists:places,id',
            'vehicle_type'      => 'sometimes|nullable|string|max:255',
            'inclusion'         => 'sometimes|nullable|string',
    
            // Vendor pricing/availability
            'pricing_tier_id' => 'sometimes|nullable|integer|exists:vendor_pricing_tiers,id',
            'availability_id' => 'sometimes|nullable|integer|exists:vendor_availability_time_slots,id',
    
            // Non-vendor pricing fields
            'transfer_price'       => 'sometimes|nullable|numeric',
            'currency'             => 'sometimes|nullable|string|max:10',
            'price_type'           => 'sometimes|nullable|string|max:255',
            'extra_luggage_charge' => 'sometimes|nullable|numeric',
            'waiting_charge'       => 'sometimes|nullable|numeric',
    
            // Schedule fields
            'availability_type' => 'sometimes|nullable|in:always_available,specific_date,custom_schedule',
            'available_days'    => 'sometimes|nullable|array',
            'available_days.*'  => 'string',
            'time_slots'        => 'sometimes|nullable|array',
            'time_slots.*.start'=> 'required_with:time_slots|date_format:H:i',
            'time_slots.*.end'  => 'required_with:time_slots|date_format:H:i',
            'blackout_dates'    => 'sometimes|nullable|array',
            'blackout_dates.*'  => 'date',
            'minimum_lead_time' => 'sometimes|nullable|integer',
            'maximum_passengers'=> 'sometimes|nullable|integer',
    
            // Media
            'media_gallery'     => 'sometimes|nullable|array',
    
            // SEO
            'seo' => 'sometimes|array',
            'seo.meta_title'       => 'sometimes|nullable|string|max:255',
            'seo.meta_description' => 'sometimes|nullable|string',
            'seo.keywords'         => 'sometimes|nullable|string',
            'seo.og_image_url'     => 'sometimes|nullable|string',
            'seo.canonical_url'    => 'sometimes|nullable|string',
            'seo.schema_type'      => 'sometimes|nullable|string',
            'seo.schema_data'      => 'sometimes|nullable|array',

            // Addons
            'addons' => 'sometimes|nullable|array',
            'addons.*' => 'integer|exists:addons,id',
        ]);
    
        // Auto-resolve pickup/dropoff from transfer_route if provided
        $resolved = $this->resolveFromRoute($validatedData);

        $isVendorFinal = array_key_exists('is_vendor', $validatedData)
            ? (bool) $validatedData['is_vendor']
            : (bool) optional(TransferVendorRoute::where('transfer_id', $transfer->id)->first())->is_vendor;

        if (! $isVendorFinal && $request->has('transfer_route_id')) {
            $routeId = $validatedData['transfer_route_id'] ?? null;
            if (! $routeId) {
                return response()->json([
                    'message' => 'Selected route must have place endpoints for transfers.',
                    'errors'  => ['transfer_route_id' => ['Selected route must have place endpoints for transfers.']],
                ], 422);
            }
            $route = TransferRoute::find($routeId);
            if (! $route || $route->origin_type !== 'place' || $route->destination_type !== 'place') {
                return response()->json([
                    'message' => 'Selected route must have place endpoints for transfers.',
                    'errors'  => ['transfer_route_id' => ['Selected route must have place endpoints for transfers.']],
                ], 422);
            }
        }

        // === Update Transfer ===
        $transfer->fill($validatedData);
        if (array_key_exists('transfer_route_id', $validatedData)) {
            $transfer->transfer_route_id = $validatedData['transfer_route_id'];
        }
        $transfer->save();

        // === Update TransferVendorRoute ===
        if (!empty($validatedData['is_vendor']) || $request->hasAny(['vendor_id', 'route_id', 'transfer_route_id', 'pickup_location', 'dropoff_location', 'pickup_place_id', 'dropoff_place_id', 'vehicle_type', 'inclusion'])) {
            $vendorRoute = TransferVendorRoute::where('transfer_id', $transfer->id)->first();
            if ($vendorRoute) {
                $vendorRoute->update([
                    'is_vendor'        => $validatedData['is_vendor'] ?? $vendorRoute->is_vendor,
                    'vendor_id'        => $validatedData['vendor_id'] ?? $vendorRoute->vendor_id,
                    'route_id'         => $validatedData['route_id'] ?? $vendorRoute->route_id,
                    'pickup_location'  => $vendorRoute->pickup_location,
                    'dropoff_location' => $vendorRoute->dropoff_location,
                    'pickup_place_id'  => $resolved['pickup_place_id'] ?? $vendorRoute->pickup_place_id,
                    'dropoff_place_id' => $resolved['dropoff_place_id'] ?? $vendorRoute->dropoff_place_id,
                    'vehicle_type'     => $validatedData['vehicle_type'] ?? $vendorRoute->vehicle_type,
                    'inclusion'        => $validatedData['inclusion'] ?? $vendorRoute->inclusion,
                ]);
            }
        }

        // === Update TransferPricingAvailability ===
        if ($request->hasAny(['pricing_tier_id', 'availability_id', 'transfer_price', 'currency', 'price_type', 'extra_luggage_charge', 'waiting_charge'])) {
            $pricingAvailability = TransferPricingAvailability::where('transfer_id', $transfer->id)->first();
            if ($pricingAvailability) {
                $pricingAvailability->update([
                    'pricing_tier_id'      => $validatedData['pricing_tier_id'] ?? $pricingAvailability->pricing_tier_id,
                    'availability_id'      => $validatedData['availability_id'] ?? $pricingAvailability->availability_id,
                    'transfer_price'       => $validatedData['transfer_price'] ?? $resolved['base_price'] ?? $pricingAvailability->transfer_price,
                    'currency'             => $validatedData['currency'] ?? $resolved['currency'] ?? $pricingAvailability->currency,
                    'price_type'           => $validatedData['price_type'] ?? $pricingAvailability->price_type,
                    'extra_luggage_charge' => $validatedData['extra_luggage_charge'] ?? $pricingAvailability->extra_luggage_charge,
                    'waiting_charge'       => $validatedData['waiting_charge'] ?? $pricingAvailability->waiting_charge,
                ]);
            }
        }
    
        // === Update TransferSchedule ===
        if ($request->hasAny(['availability_type', 'available_days', 'time_slots', 'blackout_dates', 'minimum_lead_time', 'maximum_passengers'])) {
            $schedule = TransferSchedule::where('transfer_id', $transfer->id)->first();
            if ($schedule) {
                $schedule->update([
                    'availability_type' => $validatedData['availability_type'] ?? $schedule->availability_type,
                    'available_days'    => isset($validatedData['available_days']) ? implode(',', $validatedData['available_days']) : $schedule->available_days,
                    'time_slots'        => isset($validatedData['time_slots']) ? json_encode($validatedData['time_slots']) : $schedule->time_slots,
                    'blackout_dates'    => isset($validatedData['blackout_dates']) ? json_encode($validatedData['blackout_dates']) : $schedule->blackout_dates,
                    'minimum_lead_time' => $validatedData['minimum_lead_time'] ?? $schedule->minimum_lead_time,
                    'maximum_passengers'=> $validatedData['maximum_passengers'] ?? $schedule->maximum_passengers,
                ]);
            }
        }
    
        // === Update Media Gallery ===
        if (isset($validatedData['media_gallery'])) {
            $hasFeatured = false;
            $mediaGallery = collect($validatedData['media_gallery'])
                ->filter(function ($item) {
                    return isset($item['media_id']) && $item['media_id'] !== null;
                })
                ->map(function ($item) use ($transfer, &$hasFeatured) {
                    $isFeatured = $item['is_featured'] ?? false;
                    if ($isFeatured) {
                        if ($hasFeatured) {
                            $isFeatured = false;
                        } else {
                            $hasFeatured = true;
                        }
                    }
                    return [
                        'id'         => $item['id'] ?? null,
                        'transfer_id'=> $transfer->id,
                        'media_id'   => $item['media_id'],
                        'is_featured'=> $isFeatured,
                    ];
                })->toArray();

            // Delete missing, update/create existing
            $existingIds = $transfer->mediaGallery->pluck('id')->toArray();
            $incomingIds = collect($mediaGallery)->pluck('id')->filter()->toArray();
            $deleteIds = array_diff($existingIds, $incomingIds);
            if (!empty($deleteIds)) {
                TransferMediaGallery::whereIn('id', $deleteIds)->delete();
            }

            foreach ($mediaGallery as $item) {
                if (!empty($item['id'])) {
                    $model = TransferMediaGallery::find($item['id']);
                    if ($model) {
                        $model->fill($item)->save();
                    }
                } else {
                    TransferMediaGallery::create($item);
                }
            }
        }
    
        // === Update SEO ===
        if (isset($validatedData['seo'])) {
            $seo = TransferSeo::where('transfer_id', $transfer->id)->first();
            if ($seo) {
                $seo->update([
                    'meta_title'       => $validatedData['seo']['meta_title'] ?? $seo->meta_title,
                    'meta_description' => $validatedData['seo']['meta_description'] ?? $seo->meta_description,
                    'keywords'         => $validatedData['seo']['keywords'] ?? $seo->keywords,
                    'og_image_url'     => $validatedData['seo']['og_image_url'] ?? $seo->og_image_url,
                    'canonical_url'    => $validatedData['seo']['canonical_url'] ?? $seo->canonical_url,
                    'schema_type'      => $validatedData['seo']['schema_type'] ?? $seo->schema_type,
                    'schema_data'      => isset($validatedData['seo']['schema_data'])
                                            ? json_encode($validatedData['seo']['schema_data'])
                                            : $seo->schema_data,
                ]);
            }
        }

        // === Update Addons ===
        if ($request->has('addons')) {
            // Delete existing
            TransferAddon::where('transfer_id', $transfer->id)->delete();

            // Create new
            if (!empty($validatedData['addons'])) {
                foreach ($validatedData['addons'] as $addonId) {
                    TransferAddon::create([
                        'transfer_id' => $transfer->id,
                        'addon_id' => $addonId,
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Transfer updated successfully',
            'transfer' => $transfer
        ]);
    }    

    /**
     * Remove the specified transfers from storage.
     */
    public function destroy(string $id)
    {
        $transfer = Transfer::find($id);
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }

        $transfer->delete();
        return response()->json(['message' => 'Transfer deleted successfully']);
    }

    /**
     * Resolve pickup/dropoff place ids and base_price/currency from a transfer_route_id.
     * Returns array with keys: pickup_place_id, dropoff_place_id, base_price, currency.
     * Any missing element is null (caller coalesces against request input).
     */
    private function resolveFromRoute(array $data): array
    {
        $out = [
            'pickup_place_id'  => null,
            'dropoff_place_id' => null,
            'base_price'       => null,
            'currency'         => null,
        ];

        $routeId = $data['transfer_route_id'] ?? null;
        if (! $routeId) {
            return $out;
        }

        $route = TransferRoute::find($routeId);
        if (! $route) {
            return $out;
        }

        if ($route->origin_type === 'place') {
            $out['pickup_place_id'] = (int) $route->origin_id;
        }
        if ($route->destination_type === 'place') {
            $out['dropoff_place_id'] = (int) $route->destination_id;
        }

        $cell = $route->resolvedPrice();
        if ($cell) {
            $out['base_price'] = (float) $cell->base_price;
            $out['currency']   = $cell->currency;
        }

        return $out;
    }

    public function destroyMultiple(Request $request)
    {
        $ids = $request->input('ids', []);
    
        if (empty($ids)) {
            return response()->json(['message' => 'No IDs provided'], 400);
        }
    
        $deletedCount = Transfer::whereIn('id', $ids)->delete();
    
        return response()->json([
            'message' => "$deletedCount transfers deleted successfully"
        ]);
    }
}
