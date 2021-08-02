<?php

namespace Tests\Unit\Flare\Models;


use App\Flare\Models\GameMap;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Kingdom;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;

class KingdomUnitTest extends TestCase
{
    use RefreshDatabase,
        CreateKingdom,
        CreateGameUnit;

    public function testGetKingdom() {

        $kingdom = $this->createTestKingdom();

        $kingdomUnit = $this->createKingdomUnit([
            'kingdom_id'   => $kingdom->id,
            'game_unit_id' => $this->createGameUnit()->id,
            'amount'       => 500,
        ]);

        $this->assertNotNull($kingdomUnit->kingdom);
    }

    public function testGetGameUnit() {

        $kingdom = $this->createTestKingdom();

        $kingdomUnit = $this->createKingdomUnit([
            'kingdom_id'   => $kingdom->id,
            'game_unit_id' => $this->createGameUnit()->id,
            'amount'       => 500,
        ]);

        $this->assertNotNull($kingdomUnit->gameUnit);
    }

    protected function createTestKingdom(): Kingdom {
        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->getCharacter();

        return $this->createKingdom([
            'character_id'       => $character->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);
    }
}
