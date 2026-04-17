<?php

namespace Database\Seeders;

// use App\Models\User;
// use App\Models\ActivityCategory;
// use App\Models\ActivityTag;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $this->call([
            MediaSeeder::class,
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class,
            AttributeSeeder::class,
            RegionSeeder::class,        // Must run before CountrySeeder
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
            PlaceSeeder::class,
            AddonSeeder::class,
            ActivitySeeder::class,
            VendorSeeder::class,
            // Transfer domain — strict dependency order:
            // 1. Zones + locations + price matrix (no deps beyond City/Place)
            // 2. Routes (depend on Zones + Places)
            // 3. Admin transfers (depend on Routes + matrix cells for base_price)
            // 4. Legacy vendor/demo transfers last
            TransferZoneSeeder::class,
            TransferRouteSeeder::class,
            AdminTransferSeeder::class,
            TransferSeeder::class,
            ItinerarySeeder::class,
            PackageSeeder::class,
            ReviewSeeder::class,
            BlogSeeder::class,
        ]);
    }
}
