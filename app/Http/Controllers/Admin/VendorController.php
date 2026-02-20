<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Vendor;
use App\Models\VendorAvailabilityTimeSlot;
use App\Models\VendorDriver;
use App\Models\VendorDriverSchedule;
use App\Models\VendorPricingTier;
use App\Models\VendorRoute;
use App\Models\VendorVehicle;
use Illuminate\Support\Facades\DB;

class VendorController extends Controller
{
    /**
     * Display a listing of the vendors.
    */

    public function index(Request $request)
    {
        // Pagination
        $perPage = (int) $request->get('per_page', 3);
        $page = (int) $request->get('page', 1);

        // Search filter
        $search = $request->get('search');

        $query = Vendor::query()
        ->with([
                 'routes',
                 'pricingTiers',
                 'availabilityTimeSlots',
                 'vehicles',
                 'drivers.schedules'
                ]);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        // Sorting by id ascending by default
        $query->orderBy('id', 'asc');

        // Get paginated result
        $total = $query->count();
        $items = $query
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $items,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }

    public function getVendorsForSelect()
    {
        // Fetch all vendors (you can also add ordering if you like)
        $vendors = Vendor::orderBy('name', 'asc')
            ->get(['id', 'name']); // Only fetch id and name columns

        return response()->json([
            'success' => true,
            'data' => $vendors
        ]);
    }

    public function store(Request $request, $requestType)
    {
        $type = str_replace('-', '_', $requestType);
        switch ($type) {
            case 'vendor':
                return $this->storeVendor($request);
            case 'route':
                return $this->storeRoute($request);
            case 'pricing_tier':
                return $this->storePricingTier($request);
            case 'vehicle':
                return $this->storeVehicle($request);
            case 'driver':
                return $this->storeDriver($request);
            case 'schedule':
                return $this->storeSchedule($request);
            case 'availability_time_slot':
                return $this->storeAvailabilityTimeSlot($request);
            default:
                return response()->json(['error' => 'Invalid type'], 400);
        }
    }

    // === Store Methods ===
    // Vendor Base table store
    private function storeVendor($request)
    {
        // Check if email already exists
        if (Vendor::where('email', $request->email)->exists()) {
            return response()->json([
                'error' => 'The email address is already in use.'
            ], 409); // 409 Conflict
        }
    
        // Optionally check phone
        if (Vendor::where('phone', $request->phone)->exists()) {
            return response()->json([
                'error' => 'The phone number is already in use.'
            ], 409);
        }
    
        // Optionally check name
        if (Vendor::where('name', $request->name)->exists()) {
            return response()->json([
                'error' => 'A vendor with this name already exists.'
            ], 409);
        }
    
        // If all clear, create vendor
        $vendor = Vendor::create([
            'name' => $request->name,
            'description' => $request->description,
            'email' => $request->email,
            'phone' => $request->phone,
            'address' => $request->address,
            'status' => $request->status ?? 'active',
        ]);
    
        return response()->json([
            'message' => 'Vendor created successfully',
            'data' => $vendor
        ], 201);
    }    

    // Pricing Tier store
    private function storePricingTier($request)
    {
        $vendorPricingTier = VendorPricingTier::create([
            'vendor_id' => $request->vendor_id,
            'name' => $request->name,
            'description' => $request->description,
            'base_price' => $request->base_price,
            'price_per_km' => $request->price_per_km,
            'min_distance' => $request->min_distance,
            'waiting_charge' => $request->waiting_charge,
            'night_charge_multiplier' => $request->night_charge_multiplier,
            'peak_hour_multiplier' => $request->peak_hour_multiplier,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => 'Vendor Pricing Tier created successfully',
            'data' => $vendorPricingTier
        ], 201); // 201 = Created
    }

    // Route store
    private function storeRoute($request)
    {
        $vendorRoute = VendorRoute::create([
            'vendor_id' => $request->vendor_id,
            'name' => $request->name,
            'description' => $request->description ?? null,
            'start_point' => $request->start_point,
            'end_point' => $request->end_point,
            'base_price' => $request->base_price,
            'price_per_km' => $request->price_per_km,
            'status' => $request->status ?? 'active',
        ]);

        return response()->json([
            'message' => 'Vendor Route created successfully',
            'data' => $vendorRoute
        ], 201); // 201 = Created
    }

    // Vehicle store
    private function storeVehicle($request)
    {
        $vendorVehicle = VendorVehicle::create([
            'vendor_id' => $request->vendor_id,
            'vehicle_type' => $request->vehicle_type,
            'capacity' => $request->capacity,
            'make' => $request->make,
            'model' => $request->model,
            'year' => $request->year,
            'license_plate' => $request->license_plate,
            'features' => $request->features ?? null,
            'status' => $request->status ?? 'active',
            'last_maintenance' => $request->last_maintenance ?? now(),
            'next_maintenance' => $request->next_maintenance ?? now()->addMonth(),
        ]);

        return response()->json([
            'message' => 'Vendor Vehicle created successfully',
            'data' => $vendorVehicle
        ], 201);
    }

    // Driver store
    private function storeDriver($request)
    {
        $vendorDriver = VendorDriver::create([
            'vendor_id' => $request->vendor_id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone' => $request->phone,
            'license_number' => $request->license_number,
            'license_expiry' => $request->license_expiry,
            'status' => $request->status ?? 'active',
            'assigned_vehicle_id' => $request->assigned_vehicle_id,
            'languages' => $request->languages,
        ]);

        return response()->json([
            'message' => 'Vendor Driver created successfully',
            'data' => $vendorDriver
        ], 201); // 201 = Created
    }

    // Schedule store
    private function storeSchedule($request)
    {
        $vendorDriverSchedule = VendorDriverSchedule::create([
            'vendor_id' => $request->vendor_id,
            'driver_id' => $request->driver_id,
            'vehicle_id' => $request->vehicle_id,
            'date' => $request->date,
            'shift' => $request->shift,
            'time' => $request->time,
        ]);

        return response()->json([
            'message' => 'Vendor Driver Schedule created successfully',
            'data' => $vendorDriverSchedule
        ], 201); // 201 = Created
    }

    // Availability Time Slot store
    private function storeAvailabilityTimeSlot($request)
    {
        $vendorAvailabilityTimeSlot = VendorAvailabilityTimeSlot::create([
            'vendor_id' => $request->vendor_id,
            'vehicle_id' => $request->vehicle_id,
            'date' => $request->date,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'max_bookings' => $request->max_bookings,
            'price_multiplier' => $request->price_multiplier,
        ]);

        return response()->json([
            'message' => 'Vendor Availability Time Slot created successfully',
            'data' => $vendorAvailabilityTimeSlot
        ], 201); // 201 = Created
    }

    // === Update function ===
    public function update(Request $request, $requestType, $id)
    {
        $type = str_replace('-', '_', $requestType);
        switch ($type) {
            case 'vendor':
                return $this->updateVendor($request, $id);
            case 'route':
                return $this->updateRoute($request, $id);
            case 'pricing_tier':
                return $this->updatePricingTiers($request);
            case 'vehicle':
                return $this->updateVehicle($request, $id);
            case 'driver':
                return $this->updateDriver($request, $id);
            case 'schedule':
                return $this->updateSchedule($request, $id);
            case 'availability_time_slot':
                return $this->updateAvailabilityTimeSlot($request);
            default:
                return response()->json(['error' => 'Invalid type'], 400);
        }
    }

    // === Update Methods ===
    // Vendor Base table update
    private function updateVendor($request, $id)
    {
        $vendor = Vendor::findOrFail($id);

        $vendor->update([
            'name' => $request->name ?? $vendor->name,
            'description' => $request->description ?? $vendor->description,
            'email' => $request->email ?? $vendor->email,
            'phone' => $request->phone ?? $vendor->phone,
            'address' => $request->address ?? $vendor->address,
            'status' => $request->status ?? $vendor->status,
        ]);

        return response()->json([
            'message' => 'Vendor updated successfully',
            'data' => $vendor
        ]);
    }

    private function updateRoute(Request $request, $id)
    {
        $route = VendorRoute::findOrFail($id);

        $route->update([
            'vendor_id' => $request->vendor_id ?? $route->vendor_id,
            'name' => $request->name ?? $route->name,
            'description' => $request->description ?? $route->description,
            'start_point' => $request->start_point ?? $route->start_point,
            'end_point' => $request->end_point ?? $route->end_point,
            'base_price' => $request->base_price ?? $route->base_price,
            'price_per_km' => $request->price_per_km ?? $route->price_per_km,
            'status' => $request->status ?? $route->status,
        ]);

        return response()->json([
            'message' => 'Vendor Route updated successfully',
            'data' => $route
        ]);
    }

    private function updatePricingTiers(Request $request, $id)
    {
        $tier = VendorPricingTier::findOrFail($id);
    
        $tier->update([
            'vendor_id' => $request->vendor_id ?? $tier->vendor_id,
            'name' => $request->name ?? $tier->name,
            'description' => $request->description ?? $tier->description,
            'base_price' => $request->base_price ?? $tier->base_price,
            'price_per_km' => $request->price_per_km ?? $tier->price_per_km,
            'min_distance' => $request->min_distance ?? $tier->min_distance,
            'waiting_charge' => $request->waiting_charge ?? $tier->waiting_charge,
            'night_charge_multiplier' => $request->night_charge_multiplier ?? $tier->night_charge_multiplier,
            'peak_hour_multiplier' => $request->peak_hour_multiplier ?? $tier->peak_hour_multiplier,
            'status' => $request->status ?? $tier->status,
        ]);
    
        return response()->json([
            'message' => 'Vendor Pricing Tier updated successfully',
            'data' => $tier
        ]);
    }
    
    private function updateVehicle(Request $request, $id)
    {
        $vehicle = VendorVehicle::findOrFail($id);
    
        $vehicle->update([
            'vendor_id' => $request->vendor_id ?? $vehicle->vendor_id,
            'vehicle_type' => $request->vehicle_type ?? $vehicle->vehicle_type,
            'capacity' => $request->capacity ?? $vehicle->capacity,
            'make' => $request->make ?? $vehicle->make,
            'model' => $request->model ?? $vehicle->model,
            'year' => $request->year ?? $vehicle->year,
            'license_plate' => $request->license_plate ?? $vehicle->license_plate,
            'features' => $request->features ?? $vehicle->features,
            'status' => $request->status ?? $vehicle->status,
            'last_maintenance' => $request->last_maintenance ?? $vehicle->last_maintenance,
            'next_maintenance' => $request->next_maintenance ?? $vehicle->next_maintenance,
        ]);
    
        return response()->json([
            'message' => 'Vendor Vehicle updated successfully',
            'data' => $vehicle
        ]);
    }

    private function updateDriver(Request $request, $id)
    {
        $driver = VendorDriver::findOrFail($id);
    
        $driver->update([
            'vendor_id' => $request->vendor_id ?? $driver->vendor_id,
            'first_name' => $request->first_name ?? $driver->first_name,
            'last_name' => $request->last_name ?? $driver->last_name,
            'email' => $request->email ?? $driver->email,
            'phone' => $request->phone ?? $driver->phone,
            'license_number' => $request->license_number ?? $driver->license_number,
            'license_expiry' => $request->license_expiry ?? $driver->license_expiry,
            'status' => $request->status ?? $driver->status,
            'assigned_vehicle_id' => $request->assigned_vehicle_id ?? $driver->assigned_vehicle_id,
            'languages' => $request->languages ?? $driver->languages,
        ]);
    
        return response()->json([
            'message' => 'Vendor Driver updated successfully',
            'data' => $driver
        ]);
    }

    private function updateSchedule(Request $request, $id)
    {
        $schedule = VendorDriverSchedule::findOrFail($id);
    
        $schedule->update([
            'vendor_id' => $request->vendor_id ?? $schedule->vendor_id,
            'driver_id' => $request->driver_id ?? $schedule->driver_id,
            'vehicle_id' => $request->vehicle_id ?? $schedule->vehicle_id,
            'date' => $request->date ?? $schedule->date,
            'shift' => $request->shift ?? $schedule->shift,
            'time' => $request->time ?? $schedule->time,
        ]);
    
        return response()->json([
            'message' => 'Vendor Driver Schedule updated successfully',
            'data' => $schedule
        ]);
    }

    private function updateAvailabilityTimeSlot(Request $request, $id)
    {
        $slot = VendorAvailabilityTimeSlot::findOrFail($id);
    
        $slot->update([
            'vendor_id' => $request->vendor_id ?? $slot->vendor_id,
            'vehicle_id' => $request->vehicle_id ?? $slot->vehicle_id,
            'date' => $request->date ?? $slot->date,
            'start_time' => $request->start_time ?? $slot->start_time,
            'end_time' => $request->end_time ?? $slot->end_time,
            'max_bookings' => $request->max_bookings ?? $slot->max_bookings,
            'price_multiplier' => $request->price_multiplier ?? $slot->price_multiplier,
        ]);
    
        return response()->json([
            'message' => 'Vendor Availability Time Slot updated successfully',
            'data' => $slot
        ]);
    }
    

    /**
     * Get a single vendor detail.
     */

    public function show($vendorId)
    {
        $vendor = Vendor::with([
            'routes:id,vendor_id,name',
            'vehicles:id,vendor_id,vehicle_type'
        ])->findOrFail($vendorId);

        return response()->json([
            'success' => true,
            'data' => $vendor
        ]);
    }

    /**
     * Get all routes for a vendor.
     */

    public function getRoutes(Request $request, $vendorId)
    {
        // Pagination defaults
        $perPage = (int) $request->get('per_page', 3);
        $page = (int) $request->get('page', 1);

        // Search filter
        $search = $request->get('search');

        $query = VendorRoute::where('vendor_id', $vendorId);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $total = $query->count();

        $routes = $query
            ->orderBy('id', 'asc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $routes,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total
        ]);
    }
    public function getRoutesForSelect($vendorId)
    {
        // Fetch all routes for this vendor
        $routes = VendorRoute::where('vendor_id', $vendorId)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'base_price', 'price_per_km']);

        return response()->json([
            'success' => true,
            'data' => $routes
        ]);
    }


    /**
     * Get all pricing tiers for a vendor.
     */

    public function getPricingTiers(Request $request, $vendorId)
    {
        // Pagination defaults
        $perPage = (int) $request->get('per_page', 3);
        $page = (int) $request->get('page', 1);

        // Search filter
        $search = $request->get('search');

        $query = VendorPricingTier::where('vendor_id', $vendorId);

        if ($search) {
            $query->where('name', 'like', "%{$search}%");
        }

        $total = $query->count();

        $tiers = $query
            ->orderBy('id', 'asc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tiers,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total
        ]);
    }
    public function getPricingTiersForSelect($vendorId)
    {
        // Get all pricing tiers for this vendor
        $tiers = VendorPricingTier::where('vendor_id', $vendorId)
            ->orderBy('name', 'asc')
            ->get(['id', 'name', 'base_price', 'peak_hour_multiplier']);

        return response()->json([
            'success' => true,
            'data' => $tiers
        ]);
    }

    /**
     * Get all vehicles for a vendor.
     */

    public function getVehicles(Request $request, $vendorId)
    {
        // Pagination defaults
        $perPage = (int) $request->get('per_page', 3);
        $page = (int) $request->get('page', 1);

        // Search filter
        $search = $request->get('search');

        $query = VendorVehicle::where('vendor_id', $vendorId);

        if ($search) {
            $query->where('make', 'like', "%{$search}%");
        }

        $total = $query->count();

        $vehicles = $query
            ->orderBy('id', 'asc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $vehicles,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total
        ]);
    }

    public function getVehiclesfordropdown($vendorId)
    {
        $vehicles = VendorVehicle::where('vendor_id', $vendorId)
            ->orderBy('id', 'asc')
            ->get(['id', 'make', 'vehicle_type', 'model']);

        return response()->json([
            'success' => true,
            'data' => $vehicles
        ]);
    }

    /**
     * Get all drivers for a vendor.
     */
    public function getDrivers(Request $request, $vendorId)
    {
        // Pagination defaults
        $perPage = (int) $request->get('per_page', 3);
        $page = (int) $request->get('page', 1);

        // Search filter
        $search = $request->get('search');

        $query = VendorDriver::where('vendor_id', $vendorId)
        ->with('assignedVehicle');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $drivers = $query
            ->orderBy('id', 'asc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        $data = $drivers->map(function($driver) {
            return [
                'id' => $driver->id,
                'vendor_id' => $driver->vendor_id,
                'first_name' => $driver->first_name,
                'last_name' => $driver->last_name,
                'email' => $driver->email,
                'phone' => $driver->phone,
                'license_number' => $driver->license_number,
                'license_expiry' => $driver->license_expiry,
                'status' => $driver->status,
                'assigned_vehicle_id' => optional($driver->assignedVehicle)->id,
                'vehicle_make' => optional($driver->assignedVehicle)->make,
                'vehicle_model' => optional($driver->assignedVehicle)->model,
                'languages' => $driver->languages,
                'created_at' => $driver->created_at,
                'updated_at' => $driver->updated_at,
            ];
        });
        return response()->json([
            'success' => true,
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total
        ]);
    }

    public function getDriversForSchedule($vendorId)
    {
        $drivers = VendorDriver::where('vendor_id', $vendorId)
            ->orderBy('first_name')
            ->get(['id', 'first_name', 'last_name']);

        $data = $drivers->map(function($driver) {
            return [
                'id' => $driver->id,
                'name' => trim($driver->first_name . ' ' . $driver->last_name)
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Get all driver schedules for a vendor.
     */
    public function getSchedules(Request $request, $vendorId)
    {
        // Pagination
        $perPage = (int) $request->get('per_page', 3);
        $page = (int) $request->get('page', 1);
    
        // Individual Filters
        $driverId = $request->get('driver_id'); // driver id
        $vehicleId = $request->get('vehicle_id'); // vehicle id
        $shift = $request->get('shift');        // shift text
        $date = $request->get('date');          // date text
    
        // Base query: only schedules for drivers of this vendor
        $query = VendorDriverSchedule::whereIn('driver_id', function ($q) use ($vendorId) {
            $q->select('id')
              ->from('vendor_drivers')
              ->where('vendor_id', $vendorId);
        })
        ->with(['driver', 'vehicle']); 
    
        // Filter by driver_id if provided
        if ($driverId) {
            $query->where('driver_id', $driverId);
        }

        // Filter by vehicle_id if provided
        if ($vehicleId) {
            $query->where('vehicle_id', $vehicleId);
        }
    
        // Filter by shift if provided
        if ($shift) {
            $query->where('shift', 'like', "%{$shift}%");
        }
    
        // Filter by date if provided
        if ($date) {
            $query->where('date', 'like', "%{$date}%");
        }
    
        // Count total
        $total = $query->count();
    
        // Fetch paginated data
        $schedules = $query
            ->orderBy('id', 'asc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();
    
        // Format the data
        $data = $schedules->map(function($schedule) {
            return [
                'id' => $schedule->id,
                'driver_id' => $schedule->driver_id,
                'driver_name' => optional($schedule->driver)->first_name . ' ' . optional($schedule->driver)->last_name,
                'vehicle_id' => $schedule->vehicle_id,
                'vehicle_make' => optional($schedule->vehicle)->make,
                'vehicle_model' => optional($schedule->vehicle)->model,
                'date' => $schedule->date,
                'shift' => $schedule->shift,
                'time' => $schedule->time,
                'created_at' => $schedule->created_at,
                'updated_at' => $schedule->updated_at,
            ];
        });

        // Return JSON
        return response()->json([
            'success' => true,
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }    

    /**
     * Get all availability time slots for a vendor.
     */

    public function getAvailabilityTimeSlots(Request $request, $vendorId)
    {
        // Pagination defaults
        $perPage = (int) $request->get('per_page', 3);
        $page = (int) $request->get('page', 1);

        $search = $request->get('search');

        $query = VendorAvailabilityTimeSlot::where('vendor_id', $vendorId)
            ->with('vehicle');

        if ($search) {
            // Search via vehicle relation
            $query->whereHas('vehicle', function ($q) use ($search) {
                $q->where('vehicle_type', 'like', "%{$search}%");
            });
        }

        $total = $query->count();

        $slots = $query
            ->orderBy('id', 'asc')
            ->skip(($page - 1) * $perPage)
            ->take($perPage)
            ->get();

        // Format response
        $data = $slots->map(function ($slot) {
            return [
                'id' => $slot->id,
                'vehicle_id' => $slot->vehicle_id,
                'vehicle_type' => optional($slot->vehicle)->vehicle_type,
                'date' => $slot->date,
                'start_time' => $slot->start_time,
                'end_time' => $slot->end_time,
                'max_bookings' => $slot->max_bookings,
                'price_multiplier' => $slot->price_multiplier,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $total,
        ]);
    }
    public function getAvailabilityTimeSlotsForSelect($vendorId)
    {
        // Get all slots for this vendor
        $slots = VendorAvailabilityTimeSlot::where('vendor_id', $vendorId)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get(['id', 'date', 'start_time']);

        // Response
        return response()->json([
            'success' => true,
            'data' => $slots
        ]);
    }

    /**
     * (Optional) Get schedules for a specific driver.
     */
    public function getDriverSchedules($driverId)
    {
        $schedules = VendorDriverSchedule::where('driver_id', $driverId)->get();

        return response()->json($schedules);
    }

    /**
     * Remove the specified vendors from storage.
     */
    public function destroy(string $id)
    {
        $vendor = Vendor::find($id);

        if (!$vendor) {
            return response()->json(['message' => 'Vendor not found'], 404);
        }

        $vendor->delete();

        return response()->json(['message' => 'Vendor deleted successfully']);
    }
}
