<?php

namespace Tests\Unit;

use App\Models\Activity;
use App\Models\ActivityLocation;
use App\Models\City;
use App\Models\Itinerary;
use App\Models\ItineraryLocation;
use App\Models\ItineraryMeta;
use App\Models\Package;
use App\Models\PackageLocation;
use App\Support\SupportItemResolver;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SupportItemResolverTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('supportedItems')]
    public function test_it_resolves_an_item_in_the_requested_city(
        string $itemType,
        string $modelClass,
        string $locationClass,
        string $foreignKey,
        string $pathSegment,
    ): void {
        /** @var City $city */
        $city = City::factory()->create(['slug' => 'dubai']);
        /** @var Model $item */
        $item = $modelClass::factory()->create(['slug' => "{$itemType}-in-dubai"]);
        $locationClass::query()->create([
            $foreignKey => $item->getKey(),
            'city_id' => $city->getKey(),
        ]);
        $itemSlug = $item->getAttribute('slug');

        $resolver = new SupportItemResolver;
        $resolved = $resolver->resolve($itemType, (int) $item->getKey(), $itemSlug, $city->slug);

        $this->assertTrue($resolved->is($item));
        $this->assertSame($item->getAttribute('name'), $resolved->getAttribute('name'));
        $this->assertSame($pathSegment, $resolver->publicPathSegment($itemType));
    }

    #[DataProvider('supportedItems')]
    public function test_it_rejects_an_item_when_the_slug_does_not_match(
        string $itemType,
        string $modelClass,
        string $locationClass,
        string $foreignKey,
        string $pathSegment,
    ): void {
        /** @var City $city */
        $city = City::factory()->create(['slug' => 'dubai']);
        /** @var Model $item */
        $item = $modelClass::factory()->create(['slug' => "{$itemType}-in-dubai"]);
        $locationClass::query()->create([
            $foreignKey => $item->getKey(),
            'city_id' => $city->getKey(),
        ]);

        $this->expectException(ModelNotFoundException::class);

        (new SupportItemResolver)->resolve($itemType, (int) $item->getKey(), 'wrong-slug', $city->slug);
    }

    #[DataProvider('supportedItems')]
    public function test_it_rejects_an_item_when_the_city_does_not_match(
        string $itemType,
        string $modelClass,
        string $locationClass,
        string $foreignKey,
        string $pathSegment,
    ): void {
        /** @var City $city */
        $city = City::factory()->create(['slug' => 'dubai']);
        /** @var Model $item */
        $item = $modelClass::factory()->create(['slug' => "{$itemType}-in-dubai"]);
        $locationClass::query()->create([
            $foreignKey => $item->getKey(),
            'city_id' => $city->getKey(),
        ]);
        $itemSlug = $item->getAttribute('slug');

        $this->expectException(ModelNotFoundException::class);

        (new SupportItemResolver)->resolve($itemType, (int) $item->getKey(), $itemSlug, 'paris');
    }

    public function test_it_rejects_a_private_package(): void
    {
        [$package, $city] = $this->packageInCity(private: true);

        $this->expectException(ModelNotFoundException::class);

        (new SupportItemResolver)->resolve('package', $package->id, $package->slug, $city->slug);
    }

    public function test_it_rejects_a_private_itinerary(): void
    {
        [$itinerary, $city] = $this->itineraryInCity(private: true);

        $this->expectException(ModelNotFoundException::class);

        (new SupportItemResolver)->resolve('itinerary', $itinerary->id, $itinerary->slug, $city->slug);
    }

    public function test_it_rejects_a_pending_creator_itinerary(): void
    {
        [$itinerary, $city] = $this->itineraryInCity();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'status' => 'pending',
            'views_count' => 0,
            'likes_count' => 0,
        ]);

        $this->expectException(ModelNotFoundException::class);

        (new SupportItemResolver)->resolve('itinerary', $itinerary->id, $itinerary->slug, $city->slug);
    }

    public function test_it_accepts_an_approved_creator_itinerary(): void
    {
        [$itinerary, $city] = $this->itineraryInCity();
        ItineraryMeta::create([
            'itinerary_id' => $itinerary->id,
            'status' => 'approved',
            'views_count' => 0,
            'likes_count' => 0,
        ]);

        $resolved = (new SupportItemResolver)->resolve(
            'itinerary',
            $itinerary->id,
            $itinerary->slug,
            $city->slug,
        );

        $this->assertTrue($resolved->is($itinerary));
    }

    /**
     * @return array<string, array{string, class-string<Model>, class-string<Model>, string, string}>
     */
    public static function supportedItems(): array
    {
        return [
            'activity' => ['activity', Activity::class, ActivityLocation::class, 'activity_id', 'activities'],
            'package' => ['package', Package::class, PackageLocation::class, 'package_id', 'packages'],
            'itinerary' => ['itinerary', Itinerary::class, ItineraryLocation::class, 'itinerary_id', 'itineraries'],
        ];
    }

    /**
     * @return array{Package, City}
     */
    private function packageInCity(bool $private = false): array
    {
        /** @var City $city */
        $city = City::factory()->create(['slug' => 'dubai']);
        /** @var Package $package */
        $package = Package::factory()->create([
            'slug' => 'private-package',
            'private_package' => $private,
        ]);
        PackageLocation::query()->create([
            'package_id' => $package->id,
            'city_id' => $city->id,
        ]);

        return [$package, $city];
    }

    /**
     * @return array{Itinerary, City}
     */
    private function itineraryInCity(bool $private = false): array
    {
        /** @var City $city */
        $city = City::factory()->create(['slug' => 'dubai']);
        /** @var Itinerary $itinerary */
        $itinerary = Itinerary::factory()->create([
            'slug' => 'creator-itinerary',
            'private_itinerary' => $private,
        ]);
        ItineraryLocation::query()->create([
            'itinerary_id' => $itinerary->id,
            'city_id' => $city->id,
        ]);

        return [$itinerary, $city];
    }
}
