<?php

namespace Tests\Feature\Admin\Kingdoms;

use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class UnitsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateGameUnit,
        CreateGameBuilding;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        $this->createGameUnit();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;
    }

    public function testCanSeeIndex() {
        $this->createGameBuilding();

        GameBuildingUnit::create([
            'game_building_id' => GameBuilding::first()->id,
            'game_unit_id'     => GameUnit::first()->id,
            'required_level'   => 3,
        ]);

        $this->actingAs($this->user)->visitRoute('units.list')->see('Units')->see('Sample Unit');
    }

    public function testCanSeeCreate() {
        $this->actingAs($this->user)->visitRoute('units.create')->see('Create unit')->see('Unit Details');
    }

    public function testCanSeeEdit() {
        $this->actingAs($this->user)->visitRoute('units.edit', [
            'gameUnit' => GameUnit::first()->id
        ])->see('Edit Unit: Sample Unit')->see('Unit Details');
    }

    public function testCanSeeShow() {
        $building = $this->createGameBuilding();

        $building->units()->create([
            'game_building_id' => $building->id,
            'game_unit_id'     => GameUnit::first()->id,
            'required_level'   => 1,
        ]);

        $this->actingAs($this->user)->visitRoute('units.unit', [
            'gameUnit' => GameUnit::first()->id
        ])->see('Sample Unit')->see('Attributes');
    }

    public function testCanSeeShowWithKingdomBuildingAssociation() {
        $building = $this->createGameBuilding();

        $building->units()->create([
            'game_building_id' => $building->id,
            'game_unit_id'     => GameUnit::first()->id,
            'required_level'   => 1,
        ]);

        $this->actingAs($this->user)->visitRoute('units.unit', [
            'gameUnit' => GameUnit::first()->id
        ])->see('Sample Unit')->see('Attributes')->see($building->name);
    }
}
