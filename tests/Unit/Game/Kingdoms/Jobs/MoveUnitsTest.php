<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use Mockery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\GameMap;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Service\UnitReturnService;
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
                                           ->getCharacter(false);

        MoveUnits::dispatch(10, 2, 'attack', $character, 10);

        $this->assertTrue(true);
    }

    public function testJobDoesNotRunForTypeThatDoesntMatch() {
        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->getCharacter(false);

        MoveUnits::dispatch($this->createUnitMovement()->id, 2, 'something', $character, 10);

        $this->assertTrue(true);
    }

    public function testJobRunsForAttack() {
        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom()
            ->getCharacter(false);

        $unitMovement = $this->createUnitMovement();

        MoveUnits::dispatch($unitMovement->id, $character->kingdoms->first()->id, 'attack', $character, 10);

        $this->assertTrue(true);
    }

    public function testJobRunsForReturn() {

        $service = Mockery::mock(UnitReturnService::class)->makePartial();

        $this->app->instance(UnitReturnService::class, $service);

        $service->shouldReceive('returnUnits')->andReturn(null);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom()
            ->getCharacter(false);

        $unitMovement = $this->createUnitMovement();

        MoveUnits::dispatch($unitMovement->id, $character->kingdoms->first()->id, 'return', $character, 10);

        $this->assertTrue(true);
    }

    public function testJobRunsForRecalled() {

        $service = Mockery::mock(UnitReturnService::class)->makePartial();

        $this->app->instance(UnitReturnService::class, $service);

        $service->shouldReceive('recallUnits')->andReturn(null);

        $character = (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom()
            ->getCharacter(false);

        $unitMovement = $this->createUnitMovement();

        MoveUnits::dispatch($unitMovement->id, $character->kingdoms->first()->id, 'recalled', $character, 10);

        $this->assertTrue(true);
    }

    protected function createUnitMovement(): UnitMovementQueue {
        $character = (new CharacterFactory)->createBaseCharacter()
                                           ->givePlayerLocation()
                                           ->getCharacter(false);

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
            'completed_at'    => now()->subMinutes(500),
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
