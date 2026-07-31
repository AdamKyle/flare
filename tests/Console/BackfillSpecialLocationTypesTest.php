<?php

namespace Tests\Console;

use App\Flare\Values\LocationEffectValue;
use App\Flare\Values\LocationType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;
use Tests\Traits\CreateGameMap;
use Tests\Traits\CreateLocation;

class BackfillSpecialLocationTypesTest extends TestCase
{
    use CreateGameMap, CreateLocation, RefreshDatabase;

    public function testTypeNullEnemyStrengthLocationBecomesSpecial(): void
    {
        $gameMap = $this->createGameMap();

        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
            'type' => null,
        ]);

        Artisan::call('backfill:special-location-types');

        $this->assertSame(LocationType::SPECIAL->value, $location->fresh()->type);
    }

    public function testExistingTypedEnemyStrengthLocationIsSkipped(): void
    {
        $gameMap = $this->createGameMap();

        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
            'type' => LocationType::GOLD_MINES->value,
        ]);

        Artisan::call('backfill:special-location-types');

        $this->assertSame(LocationType::GOLD_MINES->value, $location->fresh()->type);
    }

    public function testLocationWithoutEnemyStrengthTypeIsNotChanged(): void
    {
        $gameMap = $this->createGameMap();

        $location = $this->createLocation([
            'game_map_id' => $gameMap->id,
            'enemy_strength_type' => null,
            'type' => null,
        ]);

        Artisan::call('backfill:special-location-types');

        $this->assertNull($location->fresh()->type);
    }

    public function testCommandReportsUpdatedCount(): void
    {
        $gameMap = $this->createGameMap();

        $this->createLocation([
            'game_map_id' => $gameMap->id,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
            'type' => null,
        ]);

        Artisan::call('backfill:special-location-types');

        $this->assertStringContainsString('Updated 1 special location type(s).', Artisan::output());
    }

    public function testCommandReportsSkippedCount(): void
    {
        $gameMap = $this->createGameMap();

        $this->createLocation([
            'game_map_id' => $gameMap->id,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_TWO_HUNDRED_FIFTY,
            'type' => LocationType::GOLD_MINES->value,
        ]);

        Artisan::call('backfill:special-location-types');

        $this->assertStringContainsString('Skipped 1 already typed location(s).', Artisan::output());
    }
}
