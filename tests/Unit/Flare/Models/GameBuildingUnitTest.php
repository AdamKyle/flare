<?php

namespace Tests\Unit\Flare\Models;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\GameBuildingUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameBuildingUnit;

class GameBuildingUnitTest extends TestCase
{
    use RefreshDatabase,
        CreateGameUnit,
        CreateGameBuilding,
        CreateGameBuildingUnit;

    public function testGetGameUnit() {

        $unit     = $this->createGameUnit();
        $building = $this->createGameBuilding();

        $this->createGameBuildingUnit([
            'game_building_id' => $building->id,
            'game_unit_id'     => $unit->id,
            'required_level'   => 1,
        ]);

        $gameBuildingUnit = GameBuildingUnit::first();

        $this->assertNotNull($gameBuildingUnit->gameUnit);
    }
}
