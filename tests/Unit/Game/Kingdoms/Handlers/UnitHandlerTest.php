<?php

namespace Tests\Unit\Game\Kingdoms\Handlers;

use App\Game\Kingdoms\Handlers\UnitHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\KingdomManagement;
use Tests\Traits\CreateGameUnit;

class UnitHandlerTest extends TestCase {

    use RefreshDatabase, CreateGameUnit;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = (new CharacterFactory)->createBaseCharacter()
                                                 ->givePlayerLocation()
                                                 ->kingdomManagement()
                                                 ->assignKingdom()
                                                 ->assignBuilding()
                                                 ->assignUnits([
                                                     'primary_target' => 'Walls',
                                                     'fall_back'      => 'Farm',
                                                     'siege_weapon'   => true,
                                                 ], 1000)
                                                 ->assignUnits([
                                                    'primary_target' => 'Walls',
                                                    'fall_back'      => 'Farm',
                                                    'siege_weapon'   => true,
                                                 ], 1000)
                                                 ->assignUnits([
                                                    'primary_target' => 'Walls',
                                                    'fall_back'      => 'Buildings',
                                                    'siege_weapon'   => true,
                                                 ], 1000);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testThereAreNoDefendingUnitsToAttack() {
        $unitsToAttack = $this->createAttackingUnits(0, 0);
        $defender      = $this->createEnemyKingdom()->getKingdom();

        $unitAttackHandler = new UnitHandler();

        foreach ($defender->units as $unit) {
            $unit->delete();
        }

        $defender = $defender->refresh();

        $unitsLeft = $unitAttackHandler->attack($defender, $unitsToAttack);

        $this->assertEquals(10, $unitsLeft[0]['amount']);
    }

    public function testAttackingUnitsHaveNoAttack() {
        $unitsToAttack = $this->createAttackingUnits(0, 0);
        $defender      = $this->createEnemyKingdom()->getKingdom();

        $unitsToAttack[0]['total_attack'] = 0;

        $unitAttackHandler = new UnitHandler();

        $defender = $defender->refresh();

        $unitsLeft = $unitAttackHandler->attack($defender, $unitsToAttack);

        $this->assertEquals(10, $unitsLeft[0]['amount']);
    }

    public function testDefendingUnitsHaveNoAttack() {
        $unitsToAttack = $this->createAttackingUnits();
        $defender      = $this->createEnemyKingdom()->getKingdom();

        $unitAttackHandler = new UnitHandler();

        foreach ($defender->units as $unit) {
            $unit->gameUnit()->update([
                'attack' => 0
            ]);
        }

        $defender = $defender->refresh();

        $unitsLeft = $unitAttackHandler->attack($defender, $unitsToAttack);

        $this->assertEquals(10, $unitsLeft[0]['amount']);
    }

    public function testAttackIsGreaterThenDefence() {
        $unitsToAttack = $this->createAttackingUnits(1000, 1000);
        $defender      = $this->createEnemyKingdom()->getKingdom();

        $unitAttackHandler = new UnitHandler();

        $unitsLeft = $unitAttackHandler->attack($defender, $unitsToAttack);

        $this->assertTrue($unitsLeft[0]['amount'] > 0);
    }

    public function testAttackIsLessThenDefence() {
        $unitsToAttack = $this->createAttackingUnits(1, 1);
        $defender      = $this->createEnemyKingdom()->getKingdom();

        $unitAttackHandler = new UnitHandler();

        $unitsLeft = $unitAttackHandler->attack($defender, $unitsToAttack);

        $this->assertEquals(0, $unitsLeft[0]['amount']);
    }

    public function testHealAttackingUnits() {
        $unitsToAttack = $this->createAttackingUnits();
        $defender      = $this->createEnemyKingdom()->assignUnits()->getKingdom();

        $unitsToAttack[] = [
            'amount'         => 1000,
            'total_attack'   => 0,
            'total_defence'  => 0,
            'unit_id'        => 1,
            'settler'        => false,
            'healer'         => true,
            'heal_for'       => 0.01,
        ];

        $unitAttackHandler = new UnitHandler();

        $unitsToAttack = $unitAttackHandler->attack($defender, $unitsToAttack);

        foreach ($unitsToAttack as $unitInfo) {
            $this->assertTrue($unitInfo['amount'] > 0);
        }
    }

    public function testFetchHealingUnits() {
        $unit = $this->createGameUnit([
            'can_heal'        => true,
            'heal_percentage' => 0.01
        ]);

        $unitsToAttack[] = [
            'amount'         => 1000,
            'total_attack'   => 0,
            'total_defence'  => 0,
            'unit_id'        => $unit->id,
            'settler'        => false,
            'healer'         => true,
            'heal_for'       => 0.01,
        ];

        $unitAttackHandler = new UnitHandler();

        $healers = $unitAttackHandler->fetchHealers($unitsToAttack);

        $this->assertNotEmpty($healers);
    }

    public function testCantFetchHealingUnits() {
        $unit = $this->createGameUnit();

        $unitsToAttack[] = [
            'amount'         => 1000,
            'total_attack'   => 0,
            'total_defence'  => 0,
            'unit_id'        => $unit->id,
            'settler'        => false,
            'healer'         => false,
            'heal_for'       => 0.0,
        ];

        $unitAttackHandler = new UnitHandler();

        $healers = $unitAttackHandler->fetchHealers($unitsToAttack);

        $this->assertEmpty($healers);
    }

    protected function createAttackingUnits(int $totalAttack = 100, int $totalDefence = 100): array {
        return [
            [
                'amount'         => 10,
                'total_attack'   => $totalAttack,
                'total_defence'  => $totalDefence,
                'unit_id'        => 1,
                'settler'        => false,
                'healer'         => false,
                'heal_for'       => 0.00,
            ],
        ];
    }

    protected function createEnemyKingdom(): KingdomManagement {
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
}
