<?php

namespace Tests\Unit\Flare\Models;

use App\Flare\Models\Character;
use App\Flare\Models\GameMap;
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

        $this->createUnitMovementQueue([
            'character_id'    => Character::first()->id,
            'from_kingdom_id' => Kingdom::first()->id,
            'to_kingdom_id'   => Kingdom::first()->id,
            'units_moving'    => [],
            'completed_at'    => now()->addDays(250),
            'started_at'      => now(),
            'moving_to_x'     => 1,
            'moving_to_y'     => 0,
            'from_x'          => 0,
            'from_y'          => 1,
            'is_attacking'    => true,
            'is_recalled'     => false,
            'is_returning'    => false,
            'is_moving'       => false,
        ]);

        $this->assertNotNull(UnitMovementQueue::first()->from_kingdom);
        $this->assertNotNull(UnitMovementQueue::first()->to_kingdom);
    }

    public function testSetUnitsInMovement() {
        $this->createTestKingdom();

        $unit = $this->createUnitMovementQueue([
            'character_id'    => Character::first()->id,
            'from_kingdom_id' => Kingdom::first()->id,
            'to_kingdom_id'   => Kingdom::first()->id,
            'units_moving'    => [],
            'completed_at'    => now()->addDays(250),
            'started_at'      => now(),
            'moving_to_x'     => 1,
            'moving_to_y'     => 0,
            'from_x'          => 0,
            'from_y'          => 1,
            'is_attacking'    => true,
            'is_recalled'     => false,
            'is_returning'    => false,
            'is_moving'       => false,
        ]);

        $unit->update([
            'units_moving' => [
                [
                    'unit_id' => $unit->id,
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
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);
    }
}
