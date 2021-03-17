<?php

namespace Tests\Feature\Admin\Kingdoms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameKingdomBuilding;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class KingdomBuildingsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateGameKingdomBuilding;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin([], $role);

        $this->createGameKingdomBuilding();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;
    }

    public function testCanSeeIndex() {
        $this->actingAs($this->user)->visitRoute('buildings.list')->see('KingdomBuildings')->see('Test KingdomBuilding');
    }

    public function testCanSeeCreate() {
        $this->actingAs($this->user)->visitRoute('buildings.create')->see('Create building')->see('KingdomBuilding Details');
    }

    public function testCanSeeEdit() {
        $this->actingAs($this->user)->visitRoute('buildings.edit', [
            'building' => 1
        ])->see('Edit KingdomBuilding: Test KingdomBuilding')->see('KingdomBuilding Details');
    } 

    public function testCanSeeShow() {
        $this->actingAs($this->user)->visitRoute('buildings.building', [
            'building' => 1
        ])->see('Test KingdomBuilding')->see('Base Details');
    }
}
