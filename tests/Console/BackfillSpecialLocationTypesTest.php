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

    public function test_type_null_enemy_strength_location_becomes_special(): void
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

    public function test_existing_typed_enemy_strength_location_is_skipped(): void
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

    public function test_location_without_enemy_strength_type_is_not_changed(): void
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

    public function test_command_reports_updated_count(): void
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

    public function test_command_reports_skipped_count(): void
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
