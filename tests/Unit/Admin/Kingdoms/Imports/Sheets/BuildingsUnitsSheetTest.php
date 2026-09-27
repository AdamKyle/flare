<?php

namespace Tests\Unit\Admin\Kingdoms\Imports\Sheets;

use App\Admin\Kingdoms\Exceptions\KingdomWorkbookException;
use App\Admin\Kingdoms\Imports\Sheets\BuildingsSheet;
use App\Admin\Kingdoms\Imports\Sheets\BuildingsUnitsSheet;
use App\Admin\Kingdoms\Services\BuildingUnitAssignmentService;
use App\Flare\Models\GameBuildingUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameBuildingUnit;
use Tests\Traits\CreateGameUnit;

class BuildingsUnitsSheetTest extends TestCase
{
    use CreateGameBuilding, CreateGameBuildingUnit, CreateGameUnit, RefreshDatabase;

    public function test_relationship_rows_resolve_exact_building_and_unit_names(): void
    {
        $barracks = $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true, 'units_per_level' => 5]);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $archers = $this->createGameUnit(['name' => 'Archers']);

        (new BuildingsUnitsSheet(new BuildingUnitAssignmentService, new BuildingsSheet))->collection(collect([
            collect(['id', 'Building', 'Unit', 'Required Level']),
            collect([null, 'Barracks', 'Archers', 6]),
            collect([null, 'Barracks', 'Spearmen', 1]),
            collect([null, '', '', '']),
        ]));

        $this->assertSame(
            [$spearmen->id => 1, $archers->id => 6],
            GameBuildingUnit::where('game_building_id', $barracks->id)->orderBy('required_level')->pluck('required_level', 'game_unit_id')->all()
        );
    }

    public function test_unresolved_building_name_fails_the_import(): void
    {
        $this->createGameUnit(['name' => 'Spearmen']);

        $this->expectException(KingdomWorkbookException::class);
        $this->expectExceptionMessage('The Building Units sheet references an unknown Building: Unknown Keep.');

        (new BuildingsUnitsSheet(new BuildingUnitAssignmentService, new BuildingsSheet))->collection(collect([
            collect(['id', 'Building', 'Unit', 'Required Level']),
            collect([null, 'Unknown Keep', 'Spearmen', 1]),
        ]));
    }

    public function test_unresolved_unit_name_fails_the_import(): void
    {
        $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true]);

        $this->expectException(KingdomWorkbookException::class);
        $this->expectExceptionMessage('The Building Units sheet references an unknown Unit: Ghost Riders.');

        (new BuildingsUnitsSheet(new BuildingUnitAssignmentService, new BuildingsSheet))->collection(collect([
            collect(['id', 'Building', 'Unit', 'Required Level']),
            collect([null, 'Barracks', 'Ghost Riders', 1]),
        ]));
    }

    public function test_missing_building_name_fails_the_import(): void
    {
        $this->createGameUnit(['name' => 'Spearmen']);

        $this->expectException(KingdomWorkbookException::class);
        $this->expectExceptionMessage('The Building Units row requires a Building name.');

        (new BuildingsUnitsSheet(new BuildingUnitAssignmentService, new BuildingsSheet))->collection(collect([
            collect(['id', 'Building', 'Unit', 'Required Level']),
            collect([null, '', 'Spearmen', 1]),
        ]));
    }

    public function test_missing_unit_name_fails_the_import(): void
    {
        $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true]);

        $this->expectException(KingdomWorkbookException::class);
        $this->expectExceptionMessage('The Building Units row requires a Unit name.');

        (new BuildingsUnitsSheet(new BuildingUnitAssignmentService, new BuildingsSheet))->collection(collect([
            collect(['id', 'Building', 'Unit', 'Required Level']),
            collect([null, 'Barracks', '', 1]),
        ]));
    }

    public function test_invalid_required_level_fails_the_import(): void
    {
        $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true]);
        $this->createGameUnit(['name' => 'Spearmen']);

        $this->expectException(KingdomWorkbookException::class);
        $this->expectExceptionMessage('The Building Units row for Barracks and Spearmen has an invalid Required Level.');

        (new BuildingsUnitsSheet(new BuildingUnitAssignmentService, new BuildingsSheet))->collection(collect([
            collect(['id', 'Building', 'Unit', 'Required Level']),
            collect([null, 'Barracks', 'Spearmen', 'invalid']),
        ]));
    }

    public function test_relationship_rows_for_a_building_that_does_not_train_units_fail_the_import(): void
    {
        $this->createGameBuilding(['name' => 'Farm', 'trains_units' => false]);
        $this->createGameUnit(['name' => 'Spearmen']);

        $this->expectException(KingdomWorkbookException::class);
        $this->expectExceptionMessage('The Building Units sheet assigns Units to Farm, which does not train Units.');

        (new BuildingsUnitsSheet(new BuildingUnitAssignmentService, new BuildingsSheet))->collection(collect([
            collect(['id', 'Building', 'Unit', 'Required Level']),
            collect([null, 'Farm', 'Spearmen', 1]),
        ]));
    }

    public function test_relationships_omitted_for_a_building_the_workbook_defines_are_removed(): void
    {
        $church = $this->createGameBuilding(['name' => 'Church', 'trains_units' => true]);
        $farm = $this->createGameBuilding(['name' => 'Farm', 'trains_units' => true]);
        $settler = $this->createGameUnit(['name' => 'Settler']);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $this->createGameBuildingUnit(['game_building_id' => $church->id, 'game_unit_id' => $spearmen->id, 'required_level' => 1]);
        $this->createGameBuildingUnit(['game_building_id' => $farm->id, 'game_unit_id' => $spearmen->id, 'required_level' => 10]);

        $buildingsSheet = new BuildingsSheet;
        $buildingsSheet->collection(collect([
            collect(['id', 'name', 'trains_units']),
            collect([$church->id, 'Church', true]),
        ]));

        (new BuildingsUnitsSheet(new BuildingUnitAssignmentService, $buildingsSheet))->collection(collect([
            collect(['id', 'Building', 'Unit', 'Required Level']),
            collect([null, 'Church', 'Settler', 1]),
        ]));

        $this->assertSame([$settler->id], GameBuildingUnit::where('game_building_id', $church->id)->pluck('game_unit_id')->all());
        $this->assertSame([$spearmen->id], GameBuildingUnit::where('game_building_id', $farm->id)->pluck('game_unit_id')->all());
    }
}
