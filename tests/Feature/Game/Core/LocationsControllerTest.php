<?php

namespace Tests\Feature\Game\Core;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateLocation;

class LocationsControllerTest extends TestCase
{
    use RefreshDatabase, CreateLocation;

    public function testCanSeeLocationName() {

        $user = (new CharacterFactory)->createBaseCharacter()
                                      ->givePlayerLocation()
                                      ->getUser();

        $location  = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
        ]);

        $this->actingAs($user)->visitRoute('game.locations.location', [
            'location' => $location,
        ])->see($location->name);
    }
}
