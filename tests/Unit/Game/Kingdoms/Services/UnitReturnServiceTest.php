<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Events\KingdomServerMessageEvent;
use App\Flare\Values\KingdomLogStatusValue;
use App\Game\Kingdoms\Service\UnitReturnService;
use App\Game\Core\Traits\KingdomCache;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\KingdomManagement;
use Tests\Traits\createCharacterKingdom;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateNpc;
use Tests\Traits\CreateUnitMovementQueue;

class UnitReturnServiceTest extends TestCase {

    use RefreshDatabase, CreateUnitMovementQueue, KingdomCache, CreateKingdom, CreateNpc;

    public function setUp(): void {
        parent::setUp();

        Queue::fake();
        Event::fake(0);
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testReturnUnits() {
        $defender = $this->createCharacterKingdom()->getKingdom();

        $this->createKingdomLog([
            'character_id'    => $defender->character->id,
            'from_kingdom_id' => $defender->id,
            'to_kingdom_id'   => $defender->id,
            'status'          => KingdomLogStatusValue::UNITS_RETURNING,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $defender->load('buildings', 'units')->toArray(),
            'new_defender'    => $defender->load('buildings', 'units')->toArray(),
            'published'       => false,
        ]);

        $unitMovementQueue = $this->createUnitMovement($defender, $defender);

        $unitReturnService = resolve(UnitReturnService::class);

        $unitReturnService->returnUnits($unitMovementQueue, $defender->character);

        $this->assertTrue(true);
    }

    public function testRecallUnits() {
        $defender = $this->createCharacterKingdom()->getKingdom();

        $this->createKingdomLog([
            'character_id'    => $defender->character->id,
            'from_kingdom_id' => $defender->id,
            'to_kingdom_id'   => $defender->id,
            'status'          => KingdomLogStatusValue::UNITS_RETURNING,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $defender->load('buildings', 'units')->toArray(),
            'new_defender'    => $defender->load('buildings', 'units')->toArray(),
            'published'       => false,
        ]);

        $unitMovementQueue = $this->createUnitMovementWhereUnitsAreMoving($defender, $defender);

        $unitReturnService = resolve(UnitReturnService::class);

        $unitReturnService->recallUnits($unitMovementQueue, $defender->character);

        $this->assertTrue(true);
    }

    protected function createCharacterKingdom(): KingdomManagement {
        return (new CharacterFactory)->createBaseCharacter()
            ->givePlayerLocation()
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'name'     => 'Walls',
                'is_walls' => true
            ])
            ->assignBuilding([
                'name'    => 'Farm',
                'is_farm' => true
            ])
            ->assignBuilding()
            ->assignUnits();
    }

    protected function createUnitMovement(Kingdom $defenderKingdom, Kingdom $attackingKingdom): UnitMovementQueue {
        return $this->createUnitMovementQueue([
            'character_id'       => $attackingKingdom->character->id,
            'from_kingdom_id'    => $attackingKingdom->id,
            'to_kingdom_id'      => $defenderKingdom->id,
            'units_moving'       => $this->getNewUnitsInMovement($attackingKingdom),
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

    protected function createUnitMovementWhereUnitsAreMoving(Kingdom $defenderKingdom, Kingdom $attackingKingdom): UnitMovementQueue {
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

    private function getNewUnitsInMovement(Kingdom $attackingKingdom): array {
        $unitsToSend = [];

        foreach ($attackingKingdom->units as $unit) {
            $unitsToSend['new_units'] = [
                [
                    'unit_id'        => $unit->game_unit_id,
                    'amount'         => $unit->amount,
                    'time_to_return' => 1,
                ]
            ];
        }

        return $unitsToSend;
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
