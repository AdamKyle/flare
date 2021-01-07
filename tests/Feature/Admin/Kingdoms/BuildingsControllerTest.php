<?php

namespace Tests\Feature\Admin\Kingdoms;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateUser;

class BuildingsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateGameBuilding;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin([], $role);

        $this->createGameBuilding();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;
    }

    public function testCanSeeIndex() {
        $this->actingAs($this->user)->visitRoute('buildings.list')->see('Buildings')->see('Test Building');
    }

    public function testCanSeeCreate() {
        $this->actingAs($this->user)->visitRoute('buildings.create')->see('Create building')->see('Building Details');
    }

    public function testCanSeeEdit() {
        $this->actingAs($this->user)->visitRoute('buildings.edit', [
            'building' => 1
        ])->see('Edit Building: Test Building')->see('Building Details');
    } 

    public function testCanSeeShow() {
        $this->actingAs($this->user)->visitRoute('buildings.building', [
            'building' => 1
        ])->see('Test Building')->see('Base Details');
    }
}
