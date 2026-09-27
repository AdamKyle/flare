<?php

namespace Tests\Unit\Admin\Kingdoms\Services;

use App\Admin\Kingdoms\Services\BuildingUnitAssignmentService;
use App\Flare\Models\GameBuildingUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameBuildingUnit;
use Tests\Traits\CreateGameUnit;

class BuildingUnitAssignmentServiceTest extends TestCase
{
    use CreateGameBuilding, CreateGameBuildingUnit, CreateGameUnit, RefreshDatabase;

    private ?BuildingUnitAssignmentService $buildingUnitAssignmentService = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->buildingUnitAssignmentService = new BuildingUnitAssignmentService;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->buildingUnitAssignmentService = null;
    }

    public function test_sync_creates_exactly_the_selected_relationships(): void
    {
        $building = $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true]);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $archers = $this->createGameUnit(['name' => 'Archers']);

        $this->buildingUnitAssignmentService->sync($building, [$spearmen->id, $archers->id], null, null);

        $this->assertEqualsCanonicalizing(
            [$spearmen->id, $archers->id],
            GameBuildingUnit::where('game_building_id', $building->id)->pluck('game_unit_id')->all()
        );
    }

    public function test_sync_removes_relationships_missing_from_the_new_selection(): void
    {
        $building = $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true]);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $archers = $this->createGameUnit(['name' => 'Archers']);
        $this->createGameBuildingUnit(['game_building_id' => $building->id, 'game_unit_id' => $spearmen->id, 'required_level' => 1]);

        $this->buildingUnitAssignmentService->sync($building, [$archers->id], null, null);

        $this->assertSame([$archers->id], GameBuildingUnit::where('game_building_id', $building->id)->pluck('game_unit_id')->all());
    }

    public function test_sync_with_no_units_removes_every_relationship(): void
    {
        $building = $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true]);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $this->createGameBuildingUnit(['game_building_id' => $building->id, 'game_unit_id' => $spearmen->id, 'required_level' => 1]);

        $this->buildingUnitAssignmentService->sync($building, [], null, null);

        $this->assertSame(0, GameBuildingUnit::where('game_building_id', $building->id)->count());
    }

    public function test_sync_leaves_no_relationships_for_a_building_that_does_not_train_units(): void
    {
        $building = $this->createGameBuilding(['name' => 'Farm', 'trains_units' => false]);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);

        $this->buildingUnitAssignmentService->sync($building, [$spearmen->id], null, null);

        $this->assertSame(0, GameBuildingUnit::where('game_building_id', $building->id)->count());
    }

    public function test_sync_assigns_required_levels_by_selection_order_and_units_per_level(): void
    {
        $building = $this->createGameBuilding(['name' => 'Barracks', 'trains_units' => true]);
        $spearmen = $this->createGameUnit(['name' => 'Spearmen']);
        $archers = $this->createGameUnit(['name' => 'Archers']);
        $cavalry = $this->createGameUnit(['name' => 'Cavalry']);

        $this->buildingUnitAssignmentService->sync($building, [$archers->id, $cavalry->id, $spearmen->id], 3, null);

        $this->assertSame(
            [$archers->id => 1, $cavalry->id => 4, $spearmen->id => 7],
            GameBuildingUnit::where('game_building_id', $building->id)->orderBy('required_level')->pluck('required_level', 'game_unit_id')->all()
        );
    }

    public function test_sync_assigns_every_unit_the_only_at_level(): void
    {
        $building = $this->createGameBuilding(['name' => 'Airship Fields', 'trains_units' => true]);
        $airship = $this->createGameUnit(['name' => 'Airship']);
        $settler = $this->createGameUnit(['name' => 'Settler']);

        $this->buildingUnitAssignmentService->sync($building, [$airship->id, $settler->id], 2, 10);

        $this->assertSame(
            [10, 10],
            GameBuildingUnit::where('game_building_id', $building->id)->pluck('required_level')->all()
        );
    }
}
