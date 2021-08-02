<?php

namespace Tests\Feature\Admin\Locations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Flare\Models\GameMap;
use App\Flare\Models\Location;
use Tests\Traits\CreateUser;
use Tests\Traits\CreateRole;
use Tests\Traits\CreateAdventure;
use Tests\Traits\CreateLocation;
use Tests\Setup\Character\CharacterFactory;

class LocationsControllerTest extends TestCase
{
    use RefreshDatabase,
        CreateUser,
        CreateRole,
        CreateLocation,
        CreateAdventure;

    private $user;

    public function setUp(): void
    {
        parent::setUp();

        $role = $this->createAdminRole();

        $this->user = $this->createAdmin($role, []);

        $this->createLocation([
            'name'                 => 'Apples',
            'game_map_id'          => GameMap::create([
                'name' => 'Apples',
                'path' => 'test',
                'default' => true,
                'kingdom_color' => '#ffffff',
            ])->id,
            'quest_reward_item_id' => null,
            'description'          => 'test',
            'is_port'              => false,
            'x'                    => 1,
            'y'                    => 1,
        ]);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->user = null;
    }

    public function testAdminCanSeeLocationsPage()
    {
        $this->actingAs($this->user)->visit(route('locations.list'))->see('Adventures');
    }

    public function testNonAdminCannotSeeLocationsPage()
    {

        $user = (new CharacterFactory)->createBaseCharacter()->getUser();

        $this->actingAs($user)->visit(route('game'))->visit(route('locations.list'))->see('You don\'t have permission to view that.');
    }

    public function testCanSeeIndexPage() {
        $this->actingAs($this->user)->visit(route('locations.list'))->see(Location::first()->name);
    }

    public function testCanSeeCreatePage() {
        $this->actingAs($this->user)->visit(route('locations.create'))->see('Create Location');
    }

    public function testCanSeeShowPage() {
        $this->actingAs($this->user)->visit(route('locations.location', [
            'location' => Location::first()->id
        ]))->see(Location::first()->name);
    }

    public function testCanSeeEditPage() {
        $this->actingAs($this->user)->visit(route('location.edit', [
            'location' => Location::first()->id
        ]))->see(Location::first()->name);
    }
}
