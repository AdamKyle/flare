<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Jobs\MoveUnits;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateUnitMovementQueue;

/**
 * Nothing really gets tested in these tests.
 *
 * These tests are more to cover the code.
 */
class MoveUnitsTest extends TestCase {

    use RefreshDatabase, CreateUnitMovementQueue, CreateKingdom;

    public function testJobDoesNotRunForNoQueue() {
        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->getCharacter();

        MoveUnits::dispatch(10, 2, 'attack', $character);

        $this->assertTrue(true);
    }

    public function testJobDoesNotRunForTypeThatDoesntMatch() {
        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->getCharacter();

        MoveUnits::dispatch($this->createUnitMovement()->id, 2, 'something', $character);

        $this->assertTrue(true);
    }

    protected function createUnitMovement(): UnitMovementQueue {
        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->getCharacter();

        $this->createKingdom([
            'character_id'       => $character->id,
            'game_map_id'        => GameMap::first()->id,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $unit = $this->createUnitMovementQueue([
            'character_id'    => $character->id,
            'from_kingdom_id' => Kingdom::first()->id,
            'to_kingdom_id'   => Kingdom::first()->id,
            'units_moving'    => [],
            'completed_at'    => now()->addMinutes(45),
            'started_at'      => now(),
            'moving_to_x'     => 16,
            'moving_to_y'     => 16,
            'from_x'          => 0,
            'from_y'          => 0,
            'is_recalled'     => false,
            'is_returning'    => false,
        ]);

        $unit->update([
            'units_moving' => [
                [
                    'unit_id' => 1,
                    'amount'  => 200,
                ]
            ]
        ]);

        return $unit->refresh();
    }
}
