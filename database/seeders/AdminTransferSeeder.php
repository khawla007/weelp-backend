<?php

namespace Database\Seeders;

use App\Models\Addon;
use App\Models\Media;
use App\Models\Place;
use App\Models\Transfer;
use App\Models\TransferAddon;
use App\Models\TransferMediaGallery;
use App\Models\TransferPricingAvailability;
use App\Models\TransferRoute;
use App\Models\TransferSchedule;
use App\Models\TransferSeo;
use App\Models\TransferVendorRoute;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminTransferSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'khawla@fanaticcoders.com'],
            ['name' => 'Khawla Admin', 'password' => Hash::make('khawla@123#'), 'role' => 'admin'],
        );

        $routes = TransferRoute::whereIn('slug', [
            'dxb-to-burj-khalifa',
            'dxb-to-palm-jumeirah',
            'dxb-to-dubai-marina',
            'dubai-mall-to-palm',
            'burj-khalifa-to-marina',
        ])->get()->keyBy('slug');

        if ($routes->isEmpty()) {
            $this->command->warn('AdminTransferSeeder: no TransferRoute rows found; run TransferRouteSeeder first.');
            return;
        }

        $transferDefs = [
            [
                'route'        => 'dxb-to-burj-khalifa',
                'slug'         => 'dxb-to-burj-khalifa',
                'name'         => 'DXB → Burj Khalifa',
                'description'  => 'Private one-way transfer from Dubai International Airport to Burj Khalifa with an English-speaking chauffeur.',
                'transfer_type'=> 'airport_transfer',
                'vehicle'      => 'sedan',
                'inclusion'    => 'Meet & greet, bottled water, WiFi, luggage assistance',
                'price_type'   => 'per_vehicle',
                'currency'     => 'USD',
                'extra_luggage'=> 10.00,  // per bag
                'waiting'      => 0.50,   // per minute
                'availability' => 'always_available',
                'days'         => ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'],
                'slots'        => [['start' => '08:00', 'end' => '09:00'], ['start' => '17:00', 'end' => '18:00']],
                'blackout'     => [],
                'lead_time'    => 2,
                'max_pax'      => 4,
                'schema_type'  => 'Product',
            ],
            [
                'route'        => 'dxb-to-palm-jumeirah',
                'slug'         => 'dxb-to-palm-jumeirah',
                'name'         => 'DXB → Palm Jumeirah',
                'description'  => 'Comfortable SUV transfer from DXB to Palm Jumeirah resorts. Child seat available on request.',
                'transfer_type'=> 'airport_transfer',
                'vehicle'      => 'suv',
                'inclusion'    => 'Meet & greet, AC vehicle, child seat available, bottled water',
                'price_type'   => 'per_vehicle',
                'currency'     => 'USD',
                'extra_luggage'=> 12.00,  // per bag
                'waiting'      => 0.60,   // per minute
                'availability' => 'always_available',
                'days'         => ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'],
                'slots'        => [['start' => '06:00', 'end' => '07:00'], ['start' => '14:00', 'end' => '15:00'], ['start' => '20:00', 'end' => '21:00']],
                'blackout'     => [],
                'lead_time'    => 3,
                'max_pax'      => 6,
                'schema_type'  => 'Service',
            ],
            [
                'route'        => 'dxb-to-dubai-marina',
                'slug'         => 'dxb-to-dubai-marina',
                'name'         => 'DXB → Dubai Marina',
                'description'  => 'Luxury sedan transfer from DXB airport to Dubai Marina with premium refreshments.',
                'transfer_type'=> 'airport_transfer',
                'vehicle'      => 'luxury_sedan',
                'inclusion'    => 'Luxury driver, refreshments, WiFi, newspapers',
                'price_type'   => 'per_person',
                'currency'     => 'USD',
                'extra_luggage'=> 15.00,  // per bag
                'waiting'      => 0.80,   // per minute
                'availability' => 'always_available',
                'days'         => ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'],
                'slots'        => [['start' => '09:00', 'end' => '10:00'], ['start' => '18:00', 'end' => '19:00']],
                'blackout'     => [],
                'lead_time'    => 4,
                'max_pax'      => 3,
                'schema_type'  => 'TouristTrip',
            ],
            [
                'route'        => 'dubai-mall-to-palm',
                'slug'         => 'dubai-mall-to-palm',
                'name'         => 'Dubai Mall → Palm Jumeirah',
                'description'  => 'Point-to-point transfer between Dubai Mall and Palm Jumeirah. Ideal for shoppers heading to beach resorts.',
                'transfer_type'=> 'point_to_point',
                'vehicle'      => 'sedan',
                'inclusion'    => 'AC vehicle, bottled water, shopping bag assistance',
                'price_type'   => 'per_vehicle',
                'currency'     => 'USD',
                'extra_luggage'=> 8.00,   // per bag
                'waiting'      => 0.50,   // per minute
                'availability' => 'always_available',
                'days'         => ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'],
                'slots'        => [['start' => '10:00', 'end' => '11:00'], ['start' => '15:00', 'end' => '16:00'], ['start' => '21:00', 'end' => '22:00']],
                'blackout'     => [],
                'lead_time'    => 1,
                'max_pax'      => 4,
                'schema_type'  => 'Service',
            ],
            [
                'route'        => 'burj-khalifa-to-marina',
                'slug'         => 'burj-khalifa-to-marina',
                'name'         => 'Burj Khalifa → Dubai Marina',
                'description'  => 'SUV transfer from Burj Khalifa to Dubai Marina with an English-speaking driver.',
                'transfer_type'=> 'tour_transfer',
                'vehicle'      => 'suv',
                'inclusion'    => 'AC vehicle, English-speaking driver, bottled water',
                'price_type'   => 'per_vehicle',
                'currency'     => 'USD',
                'extra_luggage'=> 10.00,  // per bag
                'waiting'      => 0.50,   // per minute
                'availability' => 'always_available',
                'days'         => ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'],
                'slots'        => [['start' => '11:00', 'end' => '12:00'], ['start' => '19:00', 'end' => '20:00']],
                'blackout'     => [],
                'lead_time'    => 2,
                'max_pax'      => 6,
                'schema_type'  => 'TouristAttraction',
            ],
        ];

        $addonIds = Addon::pluck('id')->all();
        $mediaIds = Media::pluck('id')->all();

        foreach ($transferDefs as $def) {
            $route = $routes[$def['route']] ?? null;
            if (! $route) {
                continue;
            }
            $originPlace = Place::find($route->origin_id);
            $destPlace   = Place::find($route->destination_id);
            if (! $originPlace || ! $destPlace) {
                $this->command->warn("AdminTransferSeeder: origin/destination place missing for route [{$def['route']}]; skipping.");
                continue;
            }

            // 1) Transfer (Basic Info)
            $transfer = Transfer::updateOrCreate(
                ['slug' => $def['slug']],
                [
                    'name'              => $def['name'],
                    'description'       => $def['description'],
                    'transfer_type'     => $def['transfer_type'],
                    'transfer_route_id' => $route->id,
                ],
            );

            // 2) Vendor route row (admin still fills pickup/dropoff + vehicle + inclusion)
            TransferVendorRoute::updateOrCreate(
                ['transfer_id' => $transfer->id],
                [
                    'is_vendor'        => false,
                    'vendor_id'        => null,
                    'route_id'         => null,
                    'pickup_location'  => $originPlace->name,
                    'dropoff_location' => $destPlace->name,
                    'pickup_place_id'  => $originPlace->id,
                    'dropoff_place_id' => $destPlace->id,
                    'vehicle_type'     => $def['vehicle'],
                    'inclusion'        => $def['inclusion'],
                ],
            );

            // 3) Pricing — transfer_price from route matrix, fallback 50
            $basePrice = optional($route->resolvedPrice())->base_price ?? 50.00;

            TransferPricingAvailability::updateOrCreate(
                ['transfer_id' => $transfer->id, 'is_vendor' => false],
                [
                    'transfer_price'       => $basePrice,
                    'currency'             => $def['currency'],
                    'price_type'           => $def['price_type'],
                    'extra_luggage_charge' => $def['extra_luggage'],
                    'waiting_charge'       => $def['waiting'],
                ],
            );

            // 4) Schedule
            TransferSchedule::updateOrCreate(
                ['transfer_id' => $transfer->id],
                [
                    'is_vendor'          => false,
                    'availability_type'  => $def['availability'],
                    'available_days'     => implode(',', $def['days']),
                    'time_slots'         => $def['slots'],
                    'blackout_dates'     => $def['blackout'],
                    'minimum_lead_time'  => $def['lead_time'],
                    'maximum_passengers' => $def['max_pax'],
                ],
            );

            // 5) SEO
            TransferSeo::updateOrCreate(
                ['transfer_id' => $transfer->id],
                [
                    'meta_title'       => $def['name'] . ' | Weelp Transfers',
                    'meta_description' => $def['description'],
                    'keywords'         => 'transfer, ' . strtolower($def['vehicle']) . ', dubai, private car, ' . strtolower($def['transfer_type']),
                    'og_image_url'     => '',
                    'canonical_url'    => '/transfers/' . $def['slug'],
                    'schema_type'      => $def['schema_type'],
                    'schema_data'      => [
                        '@context' => 'https://schema.org',
                        '@type'    => $def['schema_type'],
                        'name'     => $def['name'],
                    ],
                ],
            );

            // 6) Addons — attach 3 random
            if (! empty($addonIds)) {
                TransferAddon::where('transfer_id', $transfer->id)->delete();
                $pick = collect($addonIds)->shuffle()->take(3);
                foreach ($pick as $aid) {
                    TransferAddon::create(['transfer_id' => $transfer->id, 'addon_id' => $aid]);
                }
            }

            // 7) Media gallery — 4 random, first featured
            if (empty($mediaIds)) {
                $this->command->warn("AdminTransferSeeder: Media table is empty; skipping media gallery for [{$def['slug']}].");
            } else {
                TransferMediaGallery::where('transfer_id', $transfer->id)->delete();
                $pickMedia = collect($mediaIds)->shuffle()->take(4)->values();
                foreach ($pickMedia as $idx => $mid) {
                    TransferMediaGallery::create([
                        'transfer_id' => $transfer->id,
                        'media_id'    => $mid,
                        'is_featured' => $idx === 0,
                    ]);
                }
            }
        }
    }
}
