<?php

namespace Tests\Unit\Admin\Import\Kingdoms\Sheets;

use Tests\Traits\CreateGameUnit;

use Tests\Traits\CreateGameBuildingUnit;

use Tests\Traits\CreateGameBuilding;

use App\Admin\Import\Kingdoms\Sheets\BuildingsUnitsSheet;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class BuildingsUnitsSheetTest extends TestCase
{
    use CreateGameBuilding, CreateGameBuildingUnit, CreateGameUnit, RefreshDatabase;

    public function testCollectionPreservesOmittedRelationshipsAndUpsertsPresentRows(): void
    {
        $church = $this->createGameBuilding(['name' => 'Church']);
        $farm = $this->createGameBuilding(['name' => 'Farm']);
        $settler = $this->createGameUnit(['name' => 'Settler']);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $this->createGameBuildingUnit([
            'game_building_id' => $farm->id,
            'game_unit_id' => $spearmen->id,
            'required_level' => 10,
        ]);
        $this->createGameBuildingUnit([
            'game_building_id' => $church->id,
            'game_unit_id' => $settler->id,
            'required_level' => 1,
        ]);

        (new BuildingsUnitsSheet)->collection(new Collection([
            ['id', 'building', 'unit', 'level'],
            [null, 'Church', 'Settler', 2],
            [null, '', '', ''],
            [null, 'Unknown', 'Settler', 1],
        ]));

        $this->assertSame(2, GameBuildingUnit::count());
        $this->assertSame(10, GameBuildingUnit::where('game_building_id', $farm->id)->where('game_unit_id', $spearmen->id)->first()->required_level);
        $this->assertSame(2, GameBuildingUnit::where('game_building_id', $church->id)->where('game_unit_id', $settler->id)->first()->required_level);
    }
}
