<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VendorSeeder extends Seeder {
    public function run() {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Disable foreign key checks

        // Truncate Tables
        DB::table('vendors')->truncate();
        DB::table('vendor_routes')->truncate();
        DB::table('vendor_pricing_tiers')->truncate();
        DB::table('vendor_availability_time_slots')->truncate();
        DB::table('vendor_vehicles')->truncate();
        DB::table('vendor_drivers')->truncate();
        DB::table('vendor_driver_schedules')->truncate();

        DB::statement('SET FOREIGN_KEY_CHECKS=1;'); // Enable foreign key checks

        // Insert Vendors
        $vendors = [
            [
                'name' => 'vendor one',
                'description' => 'leading transportation provider.',
                'email' => 'vendor1@example.com',
                'phone' => '+123-456-7890',
                'address' => '123 main st, city, country',
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'vendor two',
                'description' => 'reliable taxi services.',
                'email' => 'vendor2@example.com',
                'phone' => '+987-654-3210',
                'address' => '456 elm st, city, country',
                'status' => 'inactive',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'vendor three',
                'description' => 'luxury car rentals.',
                'email' => 'vendor3@example.com',
                'phone' => '+111-222-3333',
                'address' => '789 pine st, city, country',
                'status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'vendor Four',
                'description' => 'affordable rides for everyone.',
                'email' => 'vendor4@example.com',
                'phone' => '+444-555-6666',
                'address' => '101 oak st, city, country',
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'vendor five',
                'description' => 'leading transportation provider.',
                'email' => 'vendor5@example.com',
                'phone' => '+123-456-7890',
                'address' => '123 main st, city, country',
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'vendor six',
                'description' => 'reliable taxi services.',
                'email' => 'vendor6@example.com',
                'phone' => '+987-654-3210',
                'address' => '456 elm st, city, country',
                'status' => 'inactive',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'vendor seven',
                'description' => 'luxury car rentals.',
                'email' => 'vendor7@example.com',
                'phone' => '+111-222-3333',
                'address' => '789 pine st, city, country',
                'status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'vendor eight',
                'description' => 'affordable rides for everyone.',
                'email' => 'vendor8@example.com',
                'phone' => '+444-555-6666',
                'address' => '101 oak st, city, country',
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'vendor nine',
                'description' => 'leading transportation provider.',
                'email' => 'vendor9@example.com',
                'phone' => '+123-456-7890',
                'address' => '123 main st, city, country',
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'vendor ten',
                'description' => 'reliable taxi services.',
                'email' => 'vendor10@example.com',
                'phone' => '+987-654-3210',
                'address' => '456 elm st, city, country',
                'status' => 'inactive',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'vendor eleven',
                'description' => 'luxury car rentals.',
                'email' => 'vendor11@example.com',
                'phone' => '+111-222-3333',
                'address' => '789 pine st, city, country',
                'status' => 'pending',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'vendor tweleve',
                'description' => 'affordable rides for everyone.',
                'email' => 'vendor12@example.com',
                'phone' => '+444-555-6666',
                'address' => '101 oak st, city, country',
                'status' => 'active',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];
        DB::table('vendors')->insert($vendors);

        // Get Vendor IDs
        $vendorIds = DB::table('vendors')->pluck('id');

        // Insert Routes
        foreach ($vendorIds as $vendorId) {
            DB::table('vendor_routes')->insert([
                [
                    'vendor_id' => $vendorId,
                    'name' => 'route A',
                    'description' => 'main city route',
                    'start_point' => 'downtown',
                    'end_point' => 'airport',
                    'base_price' => 100.50,
                    'price_per_km' => 10.25,
                    'status' => 'active',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'vendor_id' => $vendorId,
                    'name' => 'route B',
                    'description' => 'highway express route',
                    'start_point' => 'mall',
                    'end_point' => 'stadium',
                    'base_price' => 80.00,
                    'price_per_km' => 8.75,
                    'status' => 'inactive',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
            ]);
        }

        // Insert Pricing Tiers
        foreach ($vendorIds as $vendorId) {
            DB::table('vendor_pricing_tiers')->insert([
                [
                    'vendor_id' => $vendorId,
                    'name' => 'standard',
                    'description' => 'regular pricing for all customers',
                    'base_price' => 50.00,
                    'price_per_km' => 5.00,
                    'min_distance' => 2,
                    'waiting_charge' => 15.00,
                    'night_charge_multiplier' => 1.5,
                    'peak_hour_multiplier' => 2.0,
                    'status' => 'active',
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
            ]);
        }

        foreach ($vendorIds as $vendorId) {
            for ($v = 1; $v <= 3; $v++) {
                $vehicleId = DB::table('vendor_vehicles')->insertGetId([
                    'vendor_id' => $vendorId,
                    'vehicle_type' => 'Sedan',
                    'capacity' => 4 + $v,
                    'make' => 'Toyota',
                    'model' => 'Camry ' . $v,
                    'year' => 2020 + $v,
                    'license_plate' => 'AB-123' . $v,
                    'features' => 'Air Conditioning, GPS, Bluetooth',
                    'status' => 'active',
                    'last_maintenance' => Carbon::now()->subMonths($v),
                    'next_maintenance' => Carbon::now()->addMonths($v),
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ]);
        
                // Create 2 Drivers per Vehicle
                for ($d = 1; $d <= 2; $d++) {
                    $driverId = DB::table('vendor_drivers')->insertGetId([
                        'vendor_id' => $vendorId,
                        'first_name' => 'Driver' . $v . $d,
                        'last_name' => 'Test',
                        'email' => 'driver' . $vendorId . $v . $d . '@example.com',
                        'phone' => '+555-555-' . $vendorId . $v . $d,
                        'license_number' => 'LIC' . $vendorId . $v . $d,
                        'license_expiry' => Carbon::now()->addYears(2),
                        'status' => 'active',
                        'assigned_vehicle_id' => $vehicleId,
                        'languages' => json_encode(['English']),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
        
                    // Create 2 Schedules per Driver
                    for ($s = 1; $s <= 2; $s++) {
                        DB::table('vendor_driver_schedules')->insert([
                            'vendor_id' => $vendorId,
                            'driver_id' => $driverId,
                            'vehicle_id' => $vehicleId,
                            'date' => Carbon::now()->addDays($s),
                            'shift' => $s == 1 ? 'Morning' : 'Evening',
                            'time' => Carbon::now()->format('H:i:s'),
                            'created_at' => Carbon::now(),
                            'updated_at' => Carbon::now(),
                        ]);
                    }
                }
        
                // Create 2 Availability Slots per Vehicle
                for ($t = 1; $t <= 2; $t++) {
                    DB::table('vendor_availability_time_slots')->insert([
                        'vendor_id' => $vendorId,
                        'vehicle_id' => $vehicleId,
                        'date' => Carbon::now()->addDays($t)->format('Y-m-d'),
                        'start_time' => '0' . (8 + $t) . ':00:00',
                        'end_time' => (12 + $t) . ':00:00',
                        'max_bookings' => 5 + $t,
                        'price_multiplier' => 1.0 + ($t * 0.2),
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ]);
                }
            }
        }        

        // Insert Vehicles
        // foreach ($vendorIds as $vendorId) {
        //     $vehicleId = DB::table('vendor_vehicles')->insertGetId([
        //         'vendor_id' => $vendorId,
        //         'vehicle_type' => 'sedan',
        //         'capacity' => 4,
        //         'make' => 'toyota',
        //         'model' => 'camry',
        //         'year' => 2022,
        //         'license_plate' => 'ab-1234',
        //         'features' => 'air conditioning, gps, bluetooth',
        //         'status' => 'active',
        //         'last_maintenance' => Carbon::now()->subMonth(),
        //         'next_maintenance' => Carbon::now()->addMonth(),
        //         'created_at' => Carbon::now(),
        //         'updated_at' => Carbon::now(),
        //     ]);

        //     // Insert Drivers with unique email
        //     $driverId = DB::table('vendor_drivers')->insertGetId([
        //         'vendor_id' => $vendorId,
        //         'first_name' => 'John',
        //         'last_name' => 'Doe',
        //         'email' => 'john.doe' . $vendorId . '@example.com', // Ensure unique email
        //         'phone' => '+555-555-55' . $vendorId, // Unique phone
        //         'license_number' => 'XYZ12345' . $vendorId, // Unique license
        //         'license_expiry' => Carbon::now()->addYear(),
        //         'status' => 'active',
        //         'assigned_vehicle_id' => $vehicleId,
        //         'languages' => json_encode(['english', 'spanish']),
        //         'created_at' => Carbon::now(),
        //         'updated_at' => Carbon::now(),
        //     ]);

        //     DB::table('vendor_availability_time_slots')->insert([
        //         'vendor_id' => $vendorId, // Connect to vendor
        //         'vehicle_id' => $vehicleId, // Connect to vendor_vehicles
        //         'date' => Carbon::now()->format('Y-m-d'),
        //         'start_time' => '08:00:00',
        //         'end_time' => '18:00:00',
        //         'max_bookings' => 5,
        //         'price_multiplier' => 1.5,
        //         'created_at' => Carbon::now(),
        //         'updated_at' => Carbon::now(),
        //     ]);

        //     // Insert Driver Schedule
        //     DB::table('vendor_driver_schedules')->insert([
        //         'vendor_id' => $vendorId,
        //         'driver_id' => $driverId,
        //         'vehicle_id' => $vehicleId,
        //         'date' => Carbon::now(),
        //         'shift' => 'morning',
        //         'time' => Carbon::now()->format('H:i:s'),
        //         'created_at' => Carbon::now(),
        //         'updated_at' => Carbon::now(),
        //     ]);
        // }


        echo "Vendor Seeder Successfully Executed!\n";
    }
}
