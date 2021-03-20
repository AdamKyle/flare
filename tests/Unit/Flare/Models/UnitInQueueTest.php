<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitInQueue;
use Tests\TestCase;
use Tests\Traits\CreateKingdom;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateGameUnit;

class UnitInQueueTest extends TestCase
{
    use RefreshDatabase,
        CreateKingdom,
        CreateGameUnit;

    public function testGetCharacter() {
        $this->createTestKingdom();

        $this->createGameUnit();

        $this->createUnitQueue([
            'character_id' => 1,
            'kingdom_id'   => 1,
            'game_unit_id' => 1,
            'amount'       => 100,
        ]);

        $this->assertNotNull(UnitInQueue::first()->character);
    }

    protected function createTestKingdom(): Kingdom {
        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->getCharacter();

        return $this->createKingdom([
            'character_id'       => $character->id,
            'game_map_id'        => 1,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);
    }
}
