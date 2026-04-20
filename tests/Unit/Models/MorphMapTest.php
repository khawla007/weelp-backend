<?php

namespace Tests\Unit\Models;

use App\Models\Activity;
use App\Models\Itinerary;
use App\Models\Package;
use App\Models\Transfer;
use Illuminate\Database\Eloquent\Relations\Relation;
use Tests\TestCase;

class MorphMapTest extends TestCase
{
    public function test_morph_map_resolves_activity(): void
    {
        $map = Relation::morphMap();
        $this->assertArrayHasKey('activity', $map);
        $this->assertEquals(Activity::class, $map['activity']);
    }

    public function test_morph_map_resolves_package(): void
    {
        $map = Relation::morphMap();
        $this->assertArrayHasKey('package', $map);
        $this->assertEquals(Package::class, $map['package']);
    }

    public function test_morph_map_resolves_itinerary(): void
    {
        $map = Relation::morphMap();
        $this->assertArrayHasKey('itinerary', $map);
        $this->assertEquals(Itinerary::class, $map['itinerary']);
    }

    public function test_morph_map_resolves_transfer(): void
    {
        $map = Relation::morphMap();
        $this->assertArrayHasKey('transfer', $map);
        $this->assertEquals(Transfer::class, $map['transfer']);
    }

    public function test_morph_map_has_exactly_four_entries(): void
    {
        $map = Relation::morphMap();
        $this->assertCount(4, $map);
        $this->assertEquals(
            ['activity', 'itinerary', 'package', 'transfer'],
            collect(array_keys($map))->sort()->values()->all()
        );
    }

    public function test_morph_map_is_enforced(): void
    {
        $this->assertTrue(Relation::requiresMorphMap());
    }

    public function test_morph_aliases_match_model_item_types(): void
    {
        $this->assertEquals('activity', (new Activity())->item_type ?? 'activity');
        $this->assertEquals('package', (new Package())->item_type ?? 'package');
        $this->assertEquals('itinerary', (new Itinerary())->item_type ?? 'itinerary');
        $this->assertEquals('transfer', (new Transfer())->item_type ?? 'transfer');
    }
}
