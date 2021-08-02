<?php

namespace Tests\Feature\Admin\Races;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateRace;

class RacesControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateRace;

    private $user;

    protected $race;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        $this->race = $this->createRace();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user  = null;
        $this->race  = null;
    }

    public function testCanSeeIndexPage() {
        $this->actingAs($this->user)->visit(route('races.list'))->see('Races');
    }

    public function testCanSeeShowPage() {
        $this->actingAs($this->user)->visit(route('races.race', [
            'race' => $this->race->id,
        ]))->see($this->race->name);
    }

    public function testCanSeeCreatePage() {
        $this->actingAs($this->user)->visit(route('races.create'))->see('Create race');
    }

    public function testCanSeeEditPage() {
        $this->actingAs($this->user)->visit(route('races.edit', [
            'race' => $this->race->id,
        ]))->see($this->race->name);
    }
}
