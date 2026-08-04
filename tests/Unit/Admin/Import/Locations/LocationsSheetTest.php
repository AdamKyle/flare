<?php

namespace Tests\Unit\Admin\Import\Locations;

use App\Admin\Import\Locations\Sheets\LocationsSheet;
use App\Flare\Models\Location;
use App\Game\Maps\Values\LocationEffect;
use App\Game\Maps\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;

class LocationsSheetTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

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
}
