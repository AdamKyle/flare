<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Kingdoms\Units;

use App\Flare\View\Livewire\Admin\Kingdoms\Units\UnitsTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameBuildingUnit;
use Tests\Traits\CreateGameUnit;

class UnitsTableTest extends TestCase
{
    use CreateGameBuilding, CreateGameBuildingUnit, CreateGameUnit, RefreshDatabase;

    public function test_mount_accepts_building_model(): void
    {
        $building = $this->createGameBuilding();
        $table = new UnitsTable();

        $table->mount($building);

        $this->assertSame($building->id, $table->buildingId);
    }

    public function test_mount_accepts_building_array(): void
    {
        $table = new UnitsTable();

        $table->mount(['id' => 41]);

        $this->assertSame(41, $table->buildingId);
    }

    public function test_mount_accepts_scalar_building_id(): void
    {
        $table = new UnitsTable();

        $table->mount('52');

        $this->assertSame(52, $table->buildingId);
    }

    public function test_mount_without_building_leaves_id_null(): void
    {
        $table = new UnitsTable();

        $table->mount();

        $this->assertNull($table->buildingId);
    }

    public function test_rehydrated_scalar_building_id_builds_scoped_query_when_building_was_removed(): void
    {
        $table = new UnitsTable();
        $table->buildingId = 999999;

        $query = $table->builder();

        $this->assertSame([], $query->get()->all());
    }

    public function test_missing_building_cannot_expose_units_from_another_building(): void
    {
        $building = $this->createGameBuilding();
        $unit = $this->createGameUnit();
        $this->createGameBuildingUnit([
            'game_building_id' => $building->id,
            'game_unit_id' => $unit->id,
            'required_level' => 1,
        ]);
        $table = new UnitsTable();
        $table->buildingId = 999999;

        $this->assertFalse($table->builder()->whereKey($unit->id)->exists());
    }

    public function test_null_building_cannot_expose_units_from_another_building(): void
    {
        $building = $this->createGameBuilding();
        $unit = $this->createGameUnit();
        $this->createGameBuildingUnit([
            'game_building_id' => $building->id,
            'game_unit_id' => $unit->id,
            'required_level' => 1,
        ]);
        $table = new UnitsTable();

        $this->assertFalse($table->builder()->whereKey($unit->id)->exists());
    }
}
