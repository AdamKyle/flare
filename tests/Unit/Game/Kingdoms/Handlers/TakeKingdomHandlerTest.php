<?php

namespace Tests\Unit\Game\Kingdoms\Handlers;

use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use App\Game\Kingdoms\Handlers\TakeKingdomHandler;
use App\Game\Kingdoms\Jobs\MoveUnits;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameUnit;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateUnitMovementQueue;

class TakeKingdomHandlerTest extends TestCase {

    use RefreshDatabase, CreateKingdom, CreateGameUnit, CreateUnitMovementQueue;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testAddUnitsToKingdom() {
        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $kingdom = $this->createKingdom([
            'character_id' => $character->id,
            'game_map_id'  => $character->map->game_map_id
        ]);

        $gameUnit = $this->createGameUnit();

        $unitsToAttack[] = [
            'amount'         => 1000,
            'total_attack'   => 0,
            'total_defence'  => 0,
            'unit_id'        => $gameUnit->id,
            'settler'        => false,
            'healer'         => false,
            'heal_for'       => 0,
        ];

        Cache::put('character-kingdoms-'  . $character->map->gameMap->name . '-' . $character->id, [[
            'id' => $kingdom->id
        ]]);

        $takeKingdomHandler = resolve(TakeKingdomHandler::class);

        $takeKingdomHandler->takeKingdom($kingdom, $character, $unitsToAttack);

        $this->assertTrue($kingdom->refresh()->units->isNotEmpty());
    }

    public function testTakeKingdomWithUnitsInMovement() {
        Queue::fake();

        $character = (new CharacterFactory())->createBaseCharacter()->givePlayerLocation()->getCharacter();

        $kingdom = $this->createKingdom([
            'character_id' => $character->id,
            'game_map_id'  => $character->map->game_map_id
        ]);

        $gameUnit = $this->createGameUnit();

        $unitsToAttack[] = [
            'amount'         => 1000,
            'total_attack'   => 0,
            'total_defence'  => 0,
            'unit_id'        => $gameUnit->id,
            'settler'        => false,
            'healer'         => false,
            'heal_for'       => 0,
        ];

        Cache::put('character-kingdoms-'  . $character->map->gameMap->name . '-' . $character->id, [[
            'id' => $kingdom->id
        ]]);

        $unitMovement = $this->createUnitMovement($kingdom, $kingdom);

        $takeKingdomHandler = resolve(TakeKingdomHandler::class);

        $takeKingdomHandler->takeKingdom($kingdom, $character, $unitsToAttack);

        $this->assertTrue($kingdom->refresh()->units->isNotEmpty());

        Queue::assertPushed(MoveUnits::class);
    }

    protected function createUnitMovement(Kingdom $defenderKingdom, Kingdom $attackingKingdom): UnitMovementQueue {
        return $this->createUnitMovementQueue([
            'character_id'       => $attackingKingdom->character->id,
            'from_kingdom_id'    => $attackingKingdom->id,
            'to_kingdom_id'      => $defenderKingdom->id,
            'units_moving'       => $this->getUnitsInMovement($attackingKingdom),
            'completed_at'       => now(),
            'started_at'         => now(),
            'moving_to_x'        => 16,
            'moving_to_y'        => 16,
            'from_x'             => 32,
            'from_y'             => 32,
            'is_recalled'        => false,
            'is_returning'       => false,
        ]);
    }

    private function getUnitsInMovement(Kingdom $attackingKingdom): array {
        $unitsToSend = [];

        foreach ($attackingKingdom->units as $unit) {
            $unitsToSend[] = [
                'unit_id'        => $unit->game_unit_id,
                'amount'         => $unit->amount,
                'time_to_return' => 1,
            ];
        }

        return $unitsToSend;
    }
}
