<?php

namespace Tests\Unit\Flare\Models;

use App\Flare\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateItem;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateMonster;

class ItemDropSourcesTest extends TestCase
{
    use CreateItem, CreateLocation, CreateMonster, RefreshDatabase;

    public function test_drop_sources_returns_empty_array_for_non_quest_item(): void
    {
        $item = $this->createItem(['type' => 'weapon']);

        $this->assertSame([], $item->drop_sources);
    }

    public function test_drop_sources_returns_empty_array_when_no_monsters_drop_the_item(): void
    {
        $item = $this->createItem(['type' => 'quest']);

        $this->assertSame([], $item->drop_sources);
    }

    public function test_normal_monster_source_type_is_normal_monster(): void
    {
        $item = $this->createItem(['type' => 'quest']);

        $monster = $this->createMonster([
            'quest_item_id' => $item->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $sources = $item->refresh()->drop_sources;

        $this->assertCount(1, $sources);
        $this->assertSame('Normal Monster', $sources[0]['source_type']);
        $this->assertSame($monster->name, $sources[0]['monster_name']);
    }

    public function test_raid_monster_source_type_is_raid_monster(): void
    {
        $item = $this->createItem(['type' => 'quest']);

        $this->createMonster([
            'quest_item_id' => $item->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => true,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $sources = $item->refresh()->drop_sources;

        $this->assertCount(1, $sources);
        $this->assertSame('Raid Monster', $sources[0]['source_type']);
    }

    public function test_raid_boss_source_type_is_raid_boss(): void
    {
        $item = $this->createItem(['type' => 'quest']);

        $this->createMonster([
            'quest_item_id' => $item->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => true,
            'only_for_location_type' => null,
        ]);

        $sources = $item->refresh()->drop_sources;

        $this->assertCount(1, $sources);
        $this->assertSame('Raid Boss', $sources[0]['source_type']);
    }

    public function test_celestial_entity_source_type_is_celestial(): void
    {
        $item = $this->createItem(['type' => 'quest']);

        $this->createMonster([
            'quest_item_id' => $item->id,
            'is_celestial_entity' => true,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $sources = $item->refresh()->drop_sources;

        $this->assertCount(1, $sources);
        $this->assertSame('Celestial', $sources[0]['source_type']);
    }

    public function test_weekly_fight_monster_source_type_is_weekly_fight_monster(): void
    {
        $item = $this->createItem(['type' => 'quest']);

        $this->createMonster([
            'quest_item_id' => $item->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH,
        ]);

        $location = $this->createLocation([
            'type' => LocationType::ALCHEMY_CHURCH,
            'name' => 'Alchemy Church',
            'x' => 100,
            'y' => 200,
        ]);

        $sources = $item->refresh()->drop_sources;

        $this->assertCount(1, $sources);
        $this->assertSame('Weekly Fight Monster', $sources[0]['source_type']);
    }

    public function test_weekly_fight_monster_source_includes_location_name_and_coordinates(): void
    {
        $item = $this->createItem(['type' => 'quest']);

        $this->createMonster([
            'quest_item_id' => $item->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => LocationType::ALCHEMY_CHURCH,
        ]);

        $location = $this->createLocation([
            'type' => LocationType::ALCHEMY_CHURCH,
            'name' => 'Alchemy Church',
            'x' => 100,
            'y' => 200,
        ]);

        $sources = $item->refresh()->drop_sources;

        $this->assertCount(1, $sources);
        $this->assertSame($location->name, $sources[0]['location_name']);
        $this->assertSame($location->x, $sources[0]['location_x']);
        $this->assertSame($location->y, $sources[0]['location_y']);
    }

    public function test_drop_sources_includes_map_name_from_monster_game_map(): void
    {
        $item = $this->createItem(['type' => 'quest']);

        $monster = $this->createMonster([
            'quest_item_id' => $item->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $sources = $item->refresh()->drop_sources;

        $this->assertCount(1, $sources);
        $this->assertSame($monster->gameMap->name, $sources[0]['map_name']);
    }

    public function test_drop_sources_returns_only_one_monster_when_multiple_monsters_drop(): void
    {
        $item = $this->createItem(['type' => 'quest']);

        $firstMonster = $this->createMonster([
            'quest_item_id' => $item->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $this->createMonster([
            'quest_item_id' => $item->id,
            'is_celestial_entity' => false,
            'is_raid_monster' => false,
            'is_raid_boss' => false,
            'only_for_location_type' => null,
        ]);

        $sources = $item->refresh()->drop_sources;

        $this->assertCount(1, $sources);
        $this->assertSame($firstMonster->name, $sources[0]['monster_name']);
    }
}
