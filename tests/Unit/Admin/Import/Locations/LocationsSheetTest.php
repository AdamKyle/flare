<?php

namespace Tests\Unit\Admin\Import\Locations;

use Tests\Traits\CreateGameMap;

use App\Admin\Import\Locations\Sheets\LocationsSheet;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocationsSheetTest extends TestCase
{
    use CreateGameMap, RefreshDatabase;

    public function testImportedEnemyStrengthLocationWithoutExplicitTypeBecomesSpecial(): void
    {
        $gameMap = $this->createGameMap();

        (new LocationsSheet)->collection(collect([
            collect(['name', 'game_map_id', 'description', 'enemy_strength_type', 'x', 'y']),
            collect(['Imported Special', $gameMap->name, 'Imported special description', LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY, 16, 16]),
        ]));

        $this->assertSame(LocationType::SPECIAL->value, Location::where('name', 'Imported Special')->first()->type);
    }

    public function testImportedEnemyStrengthLocationWithExplicitTypeKeepsThatType(): void
    {
        $gameMap = $this->createGameMap();

        (new LocationsSheet)->collection(collect([
            collect(['name', 'game_map_id', 'description', 'enemy_strength_type', 'type', 'x', 'y']),
            collect(['Imported Gold Mine', $gameMap->name, 'Imported gold mine description', LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY, LocationType::GOLD_MINES->value, 16, 16]),
        ]));

        $this->assertSame(LocationType::GOLD_MINES->value, Location::where('name', 'Imported Gold Mine')->first()->type);
    }

    public function testImportedLocationWithoutEnemyStrengthTypeKeepsNullType(): void
    {
        $gameMap = $this->createGameMap();

        (new LocationsSheet)->collection(collect([
            collect(['name', 'game_map_id', 'description', 'x', 'y']),
            collect(['Imported Regular', $gameMap->name, 'Imported regular description', 16, 16]),
        ]));

        $this->assertNull(Location::where('name', 'Imported Regular')->first()->type);
    }
}
