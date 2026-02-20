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
            CountrySeeder::class,
            StateSeeder::class,
            CitySeeder::class,
            PlaceSeeder::class,
            RegionSeeder::class,
            AddonSeeder::class,
            ActivitySeeder::class,
            VendorSeeder::class,
            TransferSeeder::class,
            ItinerarySeeder::class,
            PackageSeeder::class,
            ReviewSeeder::class,
            BlogSeeder::class,
        ]);
    }
}
