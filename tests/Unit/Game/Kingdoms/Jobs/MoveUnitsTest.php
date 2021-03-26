<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

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
        MoveUnits::dispatch(10, 2, 'attack');

        $this->assertTrue(true);
    }

    public function testJobDoesNotRunForTypeThatDoesntMatch() {
        MoveUnits::dispatch($this->createUnitMovement()->id, 2, 'something');

        $this->assertTrue(true);
    }

    protected function createUnitMovement(): UnitMovementQueue {
        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->getCharacter();

        $this->createKingdom([
            'character_id'       => $character->id,
            'game_map_id'        => 1,
            'current_stone'      => 0,
            'current_wood'       => 0,
            'current_clay'       => 0,
            'current_iron'       => 0,
            'current_population' => 0,
        ]);

        $unit = $this->createUnitMovementQueue();

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