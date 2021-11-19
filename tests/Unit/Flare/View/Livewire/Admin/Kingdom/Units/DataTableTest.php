<?php

namespace Tests\Unit\Flare\View\Livewire\Admin\Kingdom\Units;

use App\Flare\Models\GameBuildingUnit;
use Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\View\Livewire\Admin\Kingdoms\Units\DataTable;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateGameUnit;

class DataTableTest extends TestCase
{
    use RefreshDatabase, CreateGameUnit, CreateGameBuilding;

    public function setUp(): void {
        parent::setUp();
    }

    public function testTheComponentLoads()
    {
        $unit = $this->createGameUnit();
        $building = $this->createGameBuilding();

        GameBuildingUnit::create([
            'game_unit_id' => $unit->id,
            'game_building_id' => $building->id,
            'required_level' => 1,
        ]);

        Livewire::test(DataTable::class)
            ->assertSee('Spearmen')
            ->set('search', 'Spearmen')
            ->assertSee('Spearmen')
            ->set('search', 'Sample 8nit')
            ->assertDontSee('Spearmen');
    }
}
