<?php

namespace Tests\Feature\Public;

use App\Models\City;
use App\Models\Category;
use App\Models\Package;
use App\Models\PackageBasePricing;
use App\Models\PackageCategory;
use App\Models\PackageLocation;
use App\Models\PackagePriceVariation;
use App\Models\PackageTag;
use App\Models\Review;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CityEndpointTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_cities(): void
    {
        City::factory()->count(2)->create();

        $response = $this->getJson('/api/cities');

        $response->assertOk();
    }

    public function test_show_city_by_slug(): void
    {
        $city = City::factory()->create(['slug' => 'test-city']);

        $response = $this->getJson('/api/city/test-city');

        $response->assertOk();
    }

    public function test_show_city_returns_404_for_missing_slug(): void
    {
        $response = $this->getJson('/api/city/nonexistent-slug');

        $response->assertNotFound();
    }

    public function test_city_all_items_can_filter_packages(): void
    {
        $city = City::factory()->create(['slug' => 'test-city']);
        $package = Package::factory()->create([
            'name' => 'City Package',
            'slug' => 'city-package',
        ]);
        PackageLocation::create([
            'package_id' => $package->id,
            'city_id' => $city->id,
        ]);
        $basePricing = PackageBasePricing::create([
            'package_id' => $package->id,
            'currency' => 'USD',
            'availability' => 'always',
        ]);
        PackagePriceVariation::create([
            'base_pricing_id' => $basePricing->id,
            'name' => 'Adult',
            'regular_price' => 125,
            'sale_price' => 100,
            'max_guests' => 4,
        ]);

        $response = $this->getJson('/api/cities/test-city/all-items?item_type=package');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.item_type', 'package')
            ->assertJsonPath('data.0.slug', 'city-package')
            ->assertJsonPath('data.0.city_slug', 'test-city');
    }

    public function test_city_package_results_hide_private_packages_and_include_approved_ratings(): void
    {
        $city = City::factory()->create(['slug' => 'test-city']);
        $publicPackage = $this->createCityPackage($city, 'Public Escape', 125);
        $this->createCityPackage($city, 'Private Escape', 90, ['private_package' => true]);
        $user = User::factory()->create();

        Review::create(['user_id' => $user->id, 'item_type' => 'package', 'item_id' => $publicPackage->id, 'rating' => 4, 'status' => 'approved']);
        Review::create(['user_id' => $user->id, 'item_type' => 'package', 'item_id' => $publicPackage->id, 'rating' => 2, 'status' => 'pending']);

        $response = $this->getJson('/api/cities/test-city/all-items?item_type=package');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Public Escape')
            ->assertJsonPath('data.0.average_rating', 4)
            ->assertJsonPath('data.0.reviews_count', 1);
    }

    public function test_city_packages_serialize_and_filter_tags(): void
    {
        $city = City::factory()->create(['slug' => 'test-city']);
        $family = Tag::factory()->create(['name' => 'Family', 'slug' => 'family']);
        $adventure = Tag::factory()->create(['name' => 'Adventure', 'slug' => 'adventure']);
        $familyPackage = $this->createCityPackage($city, 'Family Break', 150);
        $adventurePackage = $this->createCityPackage($city, 'Desert Adventure', 250);
        PackageTag::create(['package_id' => $familyPackage->id, 'tag_id' => $family->id]);
        PackageTag::create(['package_id' => $adventurePackage->id, 'tag_id' => $adventure->id]);

        $response = $this->getJson('/api/cities/test-city/all-items?item_type=package&tags=family');

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Family Break')
            ->assertJsonPath('data.0.tags.0.slug', 'family')
            ->assertJsonPath('data.0.tags.0.name', 'Family');
    }

    public function test_city_packages_support_search_price_filtering_and_price_sorting(): void
    {
        $city = City::factory()->create(['slug' => 'test-city']);
        $this->createCityPackage($city, 'Budget Beach', 100);
        $this->createCityPackage($city, 'Premium Desert', 300);
        $this->createCityPackage($city, 'Luxury Beach', 500);

        $this->getJson('/api/cities/test-city/all-items?item_type=package&search=beach')
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->getJson('/api/cities/test-city/all-items?item_type=package&min_price=200&max_price=400')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Premium Desert');

        $this->getJson('/api/cities/test-city/all-items?item_type=package&sort_by=price_asc')
            ->assertJsonPath('data.0.name', 'Budget Beach')
            ->assertJsonPath('data.2.name', 'Luxury Beach');

        $this->getJson('/api/cities/test-city/all-items?item_type=package&sort_by=price_desc')
            ->assertJsonPath('data.0.name', 'Luxury Beach')
            ->assertJsonPath('data.2.name', 'Budget Beach');
    }

    public function test_city_items_filter_by_approved_average_rating(): void
    {
        $city = City::factory()->create(['slug' => 'test-city']);
        $user = User::factory()->create();
        $highRated = $this->createCityPackage($city, 'High Rated', 200);
        $lowRated = $this->createCityPackage($city, 'Low Rated', 100);
        Review::create(['user_id' => $user->id, 'item_type' => 'package', 'item_id' => $highRated->id, 'rating' => 5, 'status' => 'approved']);
        Review::create(['user_id' => $user->id, 'item_type' => 'package', 'item_id' => $lowRated->id, 'rating' => 3, 'status' => 'approved']);

        $this->getJson('/api/cities/test-city/all-items?item_type=package&min_rating=4')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'High Rated');
    }

    public function test_city_packages_use_the_minimum_variation_as_the_listing_price(): void
    {
        $city = City::factory()->create(['slug' => 'test-city']);
        $mixedPackage = $this->createCityPackage($city, 'Mixed Price', 500);
        PackagePriceVariation::create([
            'base_pricing_id' => $mixedPackage->basePricing->id,
            'name' => 'Child',
            'regular_price' => 100,
            'sale_price' => 100,
            'max_guests' => 2,
        ]);
        $this->createCityPackage($city, 'Mid Price', 300);

        $this->getJson('/api/cities/test-city/all-items?item_type=package&min_price=200')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Mid Price');

        $this->getJson('/api/cities/test-city/all-items?item_type=package&sort_by=price_asc')
            ->assertOk()
            ->assertJsonPath('data.0.name', 'Mixed Price')
            ->assertJsonPath('data.0.listing_price', 100);
    }

    public function test_unknown_filter_slugs_return_no_city_items(): void
    {
        $city = City::factory()->create(['slug' => 'test-city']);
        $this->createCityPackage($city, 'City Package', 100);

        $this->getJson('/api/cities/test-city/all-items?item_type=package&categories=does-not-exist')
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson('/api/cities/test-city/all-items?item_type=package&tags=does-not-exist')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_city_package_filter_options_are_unique_scoped_and_filter_independent(): void
    {
        $city = City::factory()->create(['slug' => 'test-city']);
        $otherCity = City::factory()->create(['slug' => 'other-city', 'state_id' => $city->state_id]);
        $sports = Category::factory()->create(['name' => 'Sports', 'slug' => 'sports']);
        $culture = Category::factory()->create(['name' => 'Culture', 'slug' => 'culture']);
        $family = Tag::factory()->create(['name' => 'Family', 'slug' => 'family']);
        $hidden = Tag::factory()->create(['name' => 'Hidden', 'slug' => 'hidden']);
        $sportsPackage = $this->createCityPackage($city, 'Sports Break', 100);
        $culturePackage = $this->createCityPackage($city, 'Culture Break', 200);
        $otherPackage = $this->createCityPackage($otherCity, 'Other Break', 300);

        PackageCategory::create(['package_id' => $sportsPackage->id, 'category_id' => $sports->id]);
        PackageCategory::create(['package_id' => $culturePackage->id, 'category_id' => $culture->id]);
        PackageTag::create(['package_id' => $sportsPackage->id, 'tag_id' => $family->id]);
        PackageTag::create(['package_id' => $culturePackage->id, 'tag_id' => $family->id]);
        PackageTag::create(['package_id' => $otherPackage->id, 'tag_id' => $hidden->id]);

        $response = $this->getJson('/api/cities/test-city/all-items?item_type=package&categories=sports&tags=family');

        $response
            ->assertOk()
            ->assertJsonCount(2, 'available_categories')
            ->assertJsonCount(1, 'available_tags')
            ->assertJsonPath('available_categories.0.slug', 'culture')
            ->assertJsonPath('available_categories.1.slug', 'sports')
            ->assertJsonPath('available_tags.0.slug', 'family');
    }

    public function test_city_all_items_rejects_malformed_listing_queries(): void
    {
        City::factory()->create(['slug' => 'test-city']);

        foreach ([
            'page=0',
            'per_page=101',
            'sort_by=unknown',
            'categories=valid,bad%20slug',
            'tags=valid,%24bad',
            'min_price=500&max_price=100',
            'min_rating=6',
        ] as $query) {
            $this->getJson("/api/cities/test-city/all-items?{$query}")->assertUnprocessable();
        }
    }

    private function createCityPackage(City $city, string $name, int $price, array $attributes = []): Package
    {
        $package = Package::factory()->create(array_merge([
            'name' => $name,
            'slug' => str($name)->slug(),
        ], $attributes));
        PackageLocation::create(['package_id' => $package->id, 'city_id' => $city->id]);
        $basePricing = PackageBasePricing::create([
            'package_id' => $package->id,
            'currency' => 'USD',
            'availability' => 'always',
        ]);
        PackagePriceVariation::create([
            'base_pricing_id' => $basePricing->id,
            'name' => 'Adult',
            'regular_price' => $price,
            'sale_price' => $price,
            'max_guests' => 4,
        ]);

        return $package;
    }
}
