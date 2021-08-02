<?php

namespace Tests\Unit\Flare\Models;

use App\Flare\Models\GameMap;
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

    private $charactr;

    public function testGetCharacter() {
        $kingdom = $this->createTestKingdom();

        $gameUnit = $this->createGameUnit();

        $this->createUnitQueue([
            'character_id' => $this->character->id,
            'kingdom_id'   => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount'       => 100,
        ]);

        $this->assertNotNull(UnitInQueue::first()->character);
    }

    protected function createTestKingdom(): Kingdom {
        $this->character = (new CharacterFactory)->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->getCharacter();

        return $this->createKingdom([
            'character_id'       => $this->character->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);
    }
}
