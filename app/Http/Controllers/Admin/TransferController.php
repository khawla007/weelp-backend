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
    
        $vehicleType = $request->get('vehicle_type');
        $capacity = $request->get('capacity');
        $minPrice = $request->get('min_price', 0);
        $maxPrice = $request->get('max_price');
        $availabilityDate = $request->get('availability_date');
    
        $query = Transfer::query()
            ->with([
                'vendorRoutes.route',
                'vendorRoutes.vendor.vehicles',
                'vendorRoutes.vendor.availabilityTimeSlots',
                'pricingAvailability.pricingTier',
                'pricingAvailability.availability',
                'mediaGallery.media',
                'seo',
            ])
            // Vehicle type & Capacity filter
            ->when($vehicleType || $capacity, function ($q) use ($vehicleType, $capacity) {
                $q->whereHas('vendorRoutes', function ($q1) use ($vehicleType, $capacity) {
                    $q1->where(function ($q2) use ($vehicleType, $capacity) {
                        // Case 1: is_vendor = true → vendor's vehicles filter
                        $q2->where('is_vendor', true)
                            ->whereHas('vendor.vehicles', function ($q3) use ($vehicleType, $capacity) {
                                if ($vehicleType) {
                                    $q3->where('vehicle_type', $vehicleType);
                                }
                                if ($capacity) {
                                    $q3->where('capacity', $capacity);
                                }
                            });
                    })
                    ->orWhere(function ($q2) use ($vehicleType, $capacity) {
                        // Case 2: is_vendor = false → filter directly on transfer_vendor_routes
                        $q2->where('is_vendor', false);
                        if ($vehicleType) {
                            $q2->where('vehicle_type', $vehicleType);
                        }
                        // if ($capacity) {
                        //     $q2->where('capacity', '>=', $capacity);
                        // }
                    });
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
                                   $q3->whereBetween('base_price', [$minPrice, $maxPrice]);
                               } elseif ($minPrice !== null) {
                                   $q3->where('base_price', '>=', $minPrice);
                               } elseif ($maxPrice !== null) {
                                   $q3->where('base_price', '<=', $maxPrice);
                               }
                           });
                    })
                    ->orWhere(function ($q2) use ($minPrice, $maxPrice) {
                        // Transfer side
                        $q2->where('is_vendor', false);
                        if ($minPrice !== null && $maxPrice !== null) {
                            $q2->whereBetween('base_price', [$minPrice, $maxPrice]);
                        } elseif ($minPrice !== null) {
                            $q2->where('base_price', '>=', $minPrice);
                        } elseif ($maxPrice !== null) {
                            $q2->where('base_price', '<=', $maxPrice);
                        }
                    });
                });
            })                             
            // Availability date filter
            ->when($availabilityDate, function ($q) use ($availabilityDate) {
                $q->whereHas('vendorRoutes.vendor.availabilityTimeSlots', function ($q3) use ($availabilityDate) {
                    $q3->whereDate('date', $availabilityDate);
                });
            });
    
        // Sorting
        switch ($sortBy) {
            case 'price_asc':
                $query->with(['pricingAvailability.pricingTier' => function ($q) {
                    $q->orderBy('base_price', 'asc');
                }]);
                break;
            case 'price_desc':
                $query->with(['pricingAvailability.pricingTier' => function ($q) {
                    $q->orderBy('base_price', 'desc');
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
    
        $paginated = $query->paginate($perPage, ['*'], 'page', $page);
    
        $transformed = $paginated->getCollection()->map(function ($transfer) {
            $data = $transfer->toArray();
    
            $data['media_gallery'] = collect($transfer->mediaGallery)->map(function ($media) {
                return [
                    'id'       => $media->id,
                    'media_id' => $media->media_id,
                    'name'     => $media->media->name ?? null,
                    'alt_text' => $media->media->alt_text ?? null,
                    'url'      => $media->media->url ?? null,
                ];
            });
    
            return $data;
        });
    
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
    
            // Non-vendor location fields
            'pickup_location'  => 'nullable|string|max:255',
            'dropoff_location' => 'nullable|string|max:255',
            'vehicle_type'     => 'nullable|string|max:255',
            'inclusion'        => 'nullable|string',
    
            // Vendor pricing/availability
            'pricing_tier_id' => 'nullable|integer|exists:vendor_pricing_tiers,id',
            'availability_id' => 'nullable|integer|exists:vendor_availability_time_slots,id',
    
            // Non-vendor pricing fields
            'base_price'           => 'nullable|numeric',
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
                'pickup_location'      => 'required|string|max:255',
                'dropoff_location'     => 'required|string|max:255',
                'vehicle_type'         => 'required|string|max:255',
                'inclusion'            => 'required|string',
                'base_price'           => 'required|numeric',
                'currency'             => 'required|string|max:10',
                'price_type'           => 'required|string|max:255',
                'extra_luggage_charge' => 'required|numeric',
                'waiting_charge'       => 'required|numeric',      
            ]);
        }
    
        // Create Transfer
        $transfer = Transfer::create([
            'name' => $validatedData['name'],
            'slug' => $validatedData['slug'],
            'description' => $validatedData['description'] ?? null,
            'transfer_type' => $validatedData['transfer_type'],
        ]);
    
        // Create TransferVendorRoute
        TransferVendorRoute::create([
            'transfer_id'      => $transfer->id,
            'is_vendor'        => $validatedData['is_vendor'],
            'vendor_id'        => $validatedData['is_vendor'] ? $validatedData['vendor_id'] : null,
            'route_id'         => $validatedData['is_vendor'] ? $validatedData['route_id'] : null,
            'pickup_location'  => !$validatedData['is_vendor'] ? $validatedData['pickup_location'] : null,
            'dropoff_location' => !$validatedData['is_vendor'] ? $validatedData['dropoff_location'] : null,
            'vehicle_type'     => !$validatedData['is_vendor'] ? $validatedData['vehicle_type'] : null,
            'inclusion'        => !$validatedData['is_vendor'] ? $validatedData['inclusion'] : null,
        ]);
    
        // Create Pricing Availability
        TransferPricingAvailability::create([
            'transfer_id'          => $transfer->id,
            'is_vendor'            => $validatedData['is_vendor'],
            'pricing_tier_id'      => $validatedData['is_vendor'] ? $validatedData['pricing_tier_id'] : null,
            'availability_id'      => $validatedData['is_vendor'] ? $validatedData['availability_id'] : null,
            'base_price'           => !$validatedData['is_vendor'] ? $validatedData['base_price'] : null,
            'currency'             => !$validatedData['is_vendor'] ? $validatedData['currency'] : null,
            'price_type'           => !$validatedData['is_vendor'] ? $validatedData['price_type'] : null,
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
            foreach ($validatedData['media_gallery'] as $media) {
                TransferMediaGallery::create([
                    'transfer_id' => $transfer->id,
                    'media_id'    => $media['media_id'],
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
            'vendorRoutes', 
            'pricingAvailability', 
            'mediaGallery.media', 
            'schedule', 
            'seo'
        ])->find($id);
    
        if (!$transfer) {
            return response()->json(['message' => 'Transfer not found'], 404);
        }
    
        // अगर schedule मौजूद है तो उसकी fields को array में बदलना
        if ($transfer->schedule) {
            if (!empty($transfer->schedule->available_days)) {
                $transfer->schedule->available_days = explode(',', $transfer->schedule->available_days);
            }
    
            if (!empty($transfer->schedule->time_slots)) {
                $transfer->schedule->time_slots = json_decode($transfer->schedule->time_slots, true);
            }
    
            if (!empty($transfer->schedule->blackout_dates)) {
                $transfer->schedule->blackout_dates = json_decode($transfer->schedule->blackout_dates, true);
            }
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
                ];
            })->values();
            unset($transfer->mediaGallery); // nested relation hatane ke liye
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
            'slug' => 'sometimes|required|string|unique:transfers,slug,',
            'description' => 'sometimes|nullable|string',
            'transfer_type' => 'sometimes|required|string',
            'is_vendor' => 'sometimes|required|boolean',
    
            // Vendor related
            'vendor_id' => 'sometimes|nullable|integer|exists:vendors,id',
            'route_id' => 'sometimes|nullable|integer|exists:vendor_routes,id',
    
            // Non-vendor location fields
            'pickup_location'  => 'sometimes|nullable|string|max:255',
            'dropoff_location' => 'sometimes|nullable|string|max:255',
            'vehicle_type'     => 'sometimes|nullable|string|max:255',
            'inclusion'        => 'sometimes|nullable|string',
    
            // Vendor pricing/availability
            'pricing_tier_id' => 'sometimes|nullable|integer|exists:vendor_pricing_tiers,id',
            'availability_id' => 'sometimes|nullable|integer|exists:vendor_availability_time_slots,id',
    
            // Non-vendor pricing fields
            'base_price'           => 'sometimes|nullable|numeric',
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
        ]);
    
        // === Update Transfer ===
        $transfer->fill($validatedData);
        $transfer->save();
    
        // === Update TransferVendorRoute ===
        if (!empty($validatedData['is_vendor']) || $request->hasAny(['vendor_id', 'route_id', 'pickup_location', 'dropoff_location', 'vehicle_type', 'inclusion'])) {
            $vendorRoute = TransferVendorRoute::where('transfer_id', $transfer->id)->first();
            if ($vendorRoute) {
                $vendorRoute->update([
                    'is_vendor'        => $validatedData['is_vendor'] ?? $vendorRoute->is_vendor,
                    'vendor_id'        => $validatedData['vendor_id'] ?? $vendorRoute->vendor_id,
                    'route_id'         => $validatedData['route_id'] ?? $vendorRoute->route_id,
                    'pickup_location'  => $validatedData['pickup_location'] ?? $vendorRoute->pickup_location,
                    'dropoff_location' => $validatedData['dropoff_location'] ?? $vendorRoute->dropoff_location,
                    'vehicle_type'     => $validatedData['vehicle_type'] ?? $vendorRoute->vehicle_type,
                    'inclusion'        => $validatedData['inclusion'] ?? $vendorRoute->inclusion,
                ]);
            }
        }
    
        // === Update TransferPricingAvailability ===
        if ($request->hasAny(['pricing_tier_id', 'availability_id', 'base_price', 'currency', 'price_type', 'extra_luggage_charge', 'waiting_charge'])) {
            $pricingAvailability = TransferPricingAvailability::where('transfer_id', $transfer->id)->first();
            if ($pricingAvailability) {
                $pricingAvailability->update([
                    'pricing_tier_id'      => $validatedData['pricing_tier_id'] ?? $pricingAvailability->pricing_tier_id,
                    'availability_id'      => $validatedData['availability_id'] ?? $pricingAvailability->availability_id,
                    'base_price'           => $validatedData['base_price'] ?? $pricingAvailability->base_price,
                    'currency'             => $validatedData['currency'] ?? $pricingAvailability->currency,
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
            TransferMediaGallery::where('transfer_id', $transfer->id)->delete();
            foreach ($validatedData['media_gallery'] as $media) {
                TransferMediaGallery::create([
                    'transfer_id' => $transfer->id,
                    'media_id'    => $media['media_id'],
                ]);
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
