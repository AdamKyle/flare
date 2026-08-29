<?php

namespace Tests\Unit\Admin\Locations\Imports\Sheets;

use App\Admin\Locations\Imports\Sheets\LocationsSheet;
use App\Flare\Models\Location;
use App\Game\Maps\Values\LocationEffect;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateItem;

class LocationsSheetTest extends TestCase
{
    use CreateGameMap, CreateItem, RefreshDatabase;

    public function test_imported_enemy_strength_location_without_explicit_type_becomes_special(): void
    {
        $gameMap = $this->createGameMap();

        (new LocationsSheet)->collection(collect([
            collect(['name', 'game_map_id', 'description', 'enemy_strength_type', 'x', 'y']),
            collect(['Imported Special', $gameMap->name, 'Imported special description', LocationEffect::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY->value, 16, 16]),
        ]));

        $this->assertSame(LocationType::SPECIAL->value, Location::where('name', 'Imported Special')->first()->type);
    }

    public function test_imported_enemy_strength_location_with_explicit_type_keeps_that_type(): void
    {
        $gameMap = $this->createGameMap();

        (new LocationsSheet)->collection(collect([
            collect(['name', 'game_map_id', 'description', 'enemy_strength_type', 'type', 'x', 'y']),
            collect(['Imported Gold Mine', $gameMap->name, 'Imported gold mine description', LocationEffect::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY->value, LocationType::GOLD_MINES->value, 16, 16]),
        ]));

        $this->assertSame(LocationType::GOLD_MINES->value, Location::where('name', 'Imported Gold Mine')->first()->type);
    }

    public function test_imported_location_without_enemy_strength_type_keeps_null_type(): void
    {
        $gameMap = $this->createGameMap();

        (new LocationsSheet)->collection(collect([
            collect(['name', 'game_map_id', 'description', 'x', 'y']),
            collect(['Imported Regular', $gameMap->name, 'Imported regular description', 16, 16]),
        ]));

        $this->assertNull(Location::where('name', 'Imported Regular')->first()->type);
    }

    public function test_quest_item_relationships_resolve_by_name(): void
    {
        $gameMap = $this->createGameMap();
        $rewardItem = $this->createItem(['name' => 'Reward Relic']);
        $requiredItem = $this->createItem(['name' => 'Required Relic']);

        (new LocationsSheet)->collection(collect([
            collect(['name', 'game_map_id', 'description', 'quest_reward_item_id', 'required_quest_item_id', 'x', 'y']),
            collect(['Relic Grove', $gameMap->name, 'Description', $rewardItem->name, $requiredItem->name, 16, 16]),
        ]));

        $location = Location::where('name', 'Relic Grove')->first();

        $this->assertSame($rewardItem->id, $location->quest_reward_item_id);
        $this->assertSame($requiredItem->id, $location->required_quest_item_id);
    }

    public function test_invalid_location_type_invalidates_the_entire_import(): void
    {
        $gameMap = $this->createGameMap();

        (new LocationsSheet)->collection(collect([
            collect(['name', 'game_map_id', 'description', 'type', 'x', 'y']),
            collect(['Bad Type Location', $gameMap->name, 'Description', 99999, 16, 16]),
        ]));

        $this->assertNull(Location::where('name', 'Bad Type Location')->first());
    }

    public function test_invalid_later_row_prevents_earlier_row_from_being_written(): void
    {
        $gameMap = $this->createGameMap();

        (new LocationsSheet)->collection(collect([
            collect(['name', 'game_map_id', 'description', 'x', 'y']),
            collect(['Valid Earlier Location', $gameMap->name, 'Description', 16, 16]),
            collect(['Invalid Later Location', 'Does Not Exist Map', 'Description', 16, 16]),
        ]));

        $this->assertNull(Location::where('name', 'Valid Earlier Location')->first());
        $this->assertNull(Location::where('name', 'Invalid Later Location')->first());
    }

    public function test_blank_row_stops_processing_without_touching_rows_after_it(): void
    {
        $gameMap = $this->createGameMap();

        (new LocationsSheet)->collection(collect([
            collect(['name', 'game_map_id', 'description', 'x', 'y']),
            collect(['Valid Before Blank', $gameMap->name, 'Description', 16, 16]),
            collect([null, null, null, null, null]),
            collect(['Should Not Be Reached', $gameMap->name, 'Description', 16, 16]),
        ]));

        $this->assertNotNull(Location::where('name', 'Valid Before Blank')->first());
        $this->assertNull(Location::where('name', 'Should Not Be Reached')->first());
    }
}
