<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\Character;
use App\Flare\Models\KingdomLog;
use App\Game\Core\Traits\KingdomCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Service\AttackService;
use App\Flare\Models\UnitMovementQueue;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\KingdomManagement;
use Tests\Traits\CreateUnitMovementQueue;

class AttackServiceTest extends TestCase {

    use RefreshDatabase, CreateUnitMovementQueue, KingdomCache;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testSettlerUnitIsKilledWhenItsTheLastRemainingUnit() {
        $defender = $this->createKingdom()->assignUnits([
            'attack'       => 5000,
            'defence'      => 5000,
            'defender'     => true,
            'siege_weapon' => true,
        ], 3000)->assignUnits([
            'attack'   => 5000,
            'defence'  => 5000,
            'attacker' => true,
        ], 2000)->getKingdom();

        $attacker = $this->createKingdom()->assignUnits([
            'is_settler'        => true,
            'can_not_be_healed' => true,
        ]);

        $character       = $attacker->getCharacter();
        $attackerKingdom = $attacker->getKingdom();

        foreach ($defender->units as $unit) {
            $unit->update(['amount' => 10000]);
        }

        $defender = $defender->refresh();

        $unitMovementQueue = $this->createUnitMovement($defender, $attackerKingdom);

        $attackService = resolve(AttackService::class);

        $attackService->attack($unitMovementQueue, $character, $defender->id);

        $this->assertTrue(KingdomLog::all()->isNotEmpty());
    }

    public function testSettlerReducesMorale() {
        $defender = $this->createKingdom()->getKingdom();
        $attacker = $this->createKingdom()->assignUnits([
            'attack'  => 5000,
            'defence' => 5000,
            'siege_weapon' => true,
        ], 500)->assignUnits([
            'is_settler'        => true,
            'reduces_morale_by' => 0.10
        ]);

        $character = $attacker->getCharacter();

        $unitMovementQueue = $this->createUnitMovement($defender, $attacker->getKingdom());

        $attackService = resolve(AttackService::class);

        $attackService->attack($unitMovementQueue, $character, $defender->id);

        $this->assertTrue(KingdomLog::all()->isNotEmpty());

        $defender = $defender->refresh();

        $this->assertTrue($defender->current_morale < 1);
    }

    public function testSettleKingdomAfterAttack() {
        $defender = $this->createKingdom()->getKingdom();
        $attacker = $this->createKingdom()->assignUnits([
            'attack'  => 5000,
            'defence' => 5000,
            'siege_weapon' => true,
            'is_settler' => false,
        ])->assignUnits([
            'is_settler'        => true,
            'reduces_morale_by' => 0.10
        ]);

        $defender->update([
            'current_morale' => 0.10
        ]);

        foreach ($attacker->getKingdom()->units as $unit) {
            $defender->units()->create([
                'kingdom_id'   => $defender->id,
                'game_unit_id' => $unit->game_unit_id,
                'amount'       => 0,
            ]);
        }

        $defender          = $defender->refresh();

        $character         = $attacker->getCharacter();

        $unitMovementQueue = $this->createUnitMovement($defender, $attacker->getKingdom());

        $this->addKingdomToCache($character, $attacker->getKingdom());
        $this->addKingdomToCache($defender->character, $defender);

        $attackService = resolve(AttackService::class);

        $attackService->attack($unitMovementQueue, $character, $defender->id);

        $character = $character->refresh();

        $this->assertEquals(2, $character->kingdoms->count());
    }

    public function testTakeKingdomWhenMoraleIsAlreadyAtZero() {
        $defender = $this->createKingdom()->getKingdom();
        $attacker = $this->createKingdom()->assignUnits([
            'attack'  => 5000,
            'defence' => 5000,
            'siege_weapon' => true,
        ], 500)->assignUnits([
            'is_settler'        => true,
            'reduces_morale_by' => 0.10
        ]);

        $defender->update([
            'current_morale' => 0
        ]);

        foreach ($attacker->getKingdom()->units as $unit) {
            $defender->units()->create([
                'kingdom_id'   => $defender->id,
                'game_unit_id' => $unit->game_unit_id,
                'amount'       => 0,
            ]);
        }

        $defender          = $defender->refresh();

        $character         = $attacker->getCharacter();

        $unitMovementQueue = $this->createUnitMovement($defender, $attacker->getKingdom());

        $this->addKingdomToCache($character, $attacker->getKingdom());
        $this->addKingdomToCache($defender->character, $defender);

        $attackService = resolve(AttackService::class);

        $attackService->attack($unitMovementQueue, $character, $defender->id);

        $character = $character->refresh();

        $this->assertEquals(2, $character->kingdoms->count());
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

    protected function createKingdom(): KingdomManagement {
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
