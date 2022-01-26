<?php

namespace Tests\Feature\Game\Core;

use App\Flare\Models\GameMap;
use App\Flare\Values\LocationEffectValue;
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
            'game_map_id' => GameMap::first()->id,
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

    public function testCanSeeLocationNameWhereLocationEffectsMonsters() {

        $user = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->getUser();

        $location  = $this->createLocation([
            'game_map_id'         => GameMap::first()->id,
            'name'                => 'Sample',
            'description'         => 'Port',
            'is_port'             => true,
            'x'                   => 32,
            'y'                   => 32,
            'enemy_strength_type' => LocationEffectValue::INCREASE_STATS_BY_HUNDRED_MILLION
        ]);

        $this->actingAs($user)->visitRoute('game.locations.location', [
            'location' => $location,
        ])->see($location->name);
    }
}
