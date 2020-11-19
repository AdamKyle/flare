<?php

namespace Tests\Feature\Game\Core;

use Database\Seeders\GameSkillsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\CharacterSetup;
use Tests\TestCase;
use Tests\Traits\CreateLocation;
use Tests\Traits\CreateUser;

class LocationsControllerTest extends TestCase
{
    use RefreshDatabase, CreateLocation, CreateUser;

    public function testCanSeeLocationName() {
        $this->seed(GameSkillsSeeder::class);

        $character = (new CharacterSetup())
                        ->setupCharacter($this->createUser())
                        ->getCharacter();

        $location  = $this->createLocation([
            'name'        => 'Sample',
            'description' => 'Port',
            'is_port'     => true,
            'x'           => 32,
            'y'           => 32,
        ]);

        $this->actingAs($character->user)->visitRoute('game.locations.location', [
            'location' => $location,
        ])->see($location->name);
    }
}
