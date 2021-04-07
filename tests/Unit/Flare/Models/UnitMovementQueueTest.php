<?php

namespace Tests\Unit\Flare\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use Tests\TestCase;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateUnitMovementQueue;
use Tests\Setup\Character\CharacterFactory;

class UnitMovementQueueTest extends TestCase
{
    use RefreshDatabase,
        CreateKingdom,
        CreateUnitMovementQueue;

    public function testGetKingdom() {
        $this->createTestKingdom();

        $this->createUnitMovementQueue();

        $this->assertNotNull(UnitMovementQueue::first()->from_kingdom);
        $this->assertNotNull(UnitMovementQueue::first()->to_kingdom);
    }

    public function testSetUnitsInMovement() {
        $this->createTestKingdom();

        $unit = $this->createUnitMovementQueue();

        $unit->update([
            'units_moving' => [
                [
                    'unit_id' => 1,
                    'amount'  => 200,
                ]
            ]
        ]);

        $this->assertNotEmpty(UnitMovementQueue::first()->units_moving);
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
