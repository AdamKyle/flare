<?php

namespace Tests\Console;

use App\Flare\Models\Kingdom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\CreateGameBuilding;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateKingdomBuilding;
use Tests\Setup\Character\CharacterFactory;

class UpdateKingdomTest extends TestCase
{
    use RefreshDatabase, CreateKingdom, CreateKingdomBuilding, CreateGameBuilding;

    public function testUpdateKingdoms()
    {
        $character = (new CharacterFactory)
                        ->createBaseCharacter()
                        ->givePlayerLocation()
                        ->getCharacter();

        $kingdom = $this->createKingdom([
            'character_id'       => $character->id,
            'game_map_id'        => 1,
            'current_stone'      => 0,
            'current_wood'       => 110,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
            'last_walked'         => now(),
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $this->createGameBuilding([
                'is_resource_building' => true,
                'increase_wood_amount' => 150,
            ])->id,
            'kingdom_id'         => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 100,
            'current_durability' => 100,
            'max_defence'        => 100,
            'max_durability'     => 100,
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $this->createGameBuilding([
                'is_farm' => true,
            ])->id,
            'kingdom_id'         => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 100,
            'current_durability' => 100,
            'max_defence'        => 100,
            'max_durability'     => 100,
        ]);

        $this->createKingdomBuilding([
            'game_building_id'   => $this->createGameBuilding([
                'name' => 'Keep',
            ])->id,
            'kingdom_id'         => $kingdom->id,
            'level'              => 1,
            'current_defence'    => 100,
            'current_durability' => 100,
            'max_defence'        => 100,
            'max_durability'     => 100,
        ]);

        $this->assertEquals(0, $this->artisan('update:kingdom'));

        $kingdom = Kingdom::first();

        $this->assertTrue($kingdom->current_wood !== 110);
    }
}
