<?php

namespace Tests\Unit\Game\Kingdoms\Handlers;

use App\Game\Kingdoms\Handlers\UnitHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\KingdomManagement;

class UnitHandlerTest extends TestCase {

    use RefreshDatabase;

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

    public function testUnitsFailToDoMoreDamageThenWallsDefence() {
        $unitsToAttack = $this->createAtackingUnits(1, 1);
        $defender      = $this->createEnemyKingdom()->getKingdom();
        
        $unitAttackHandler = new UnitHandler();

        $unitsLeft = $unitAttackHandler->attack($defender, $unitsToAttack);

        $this->assertEquals(0, $unitsLeft[0]['amount']);
    }

    public function testUnitsDoesMoreDamageThenWalls() {
        $unitsToAttack = $this->createAtackingUnits(1000, 1000);
        $defender      = $this->createEnemyKingdom()->getKingdom();
        
        $unitAttackHandler = new UnitHandler();

        $unitAttackHandler->attack($defender, $unitsToAttack);
        
        $walls = $defender->refresh()->buildings->where('name', 'Walls')->first();

        $this->assertEquals(0, $walls->current_durability);
    }

    public function testUnitsDoNotAttackWhenTotalAttackIsZero() {
        $unitsToAttack = $this->createAtackingUnits(0, 0);
        $defender      = $this->createEnemyKingdom()->getKingdom();
        
        $unitAttackHandler = new UnitHandler();

        $unitsLeft = $unitAttackHandler->attack($defender, $unitsToAttack);
        
        $this->assertEquals(10, $unitsLeft[0]['amount']);
    }

    public function testUnitsDoNotAttackAfterWallsWhenThereAreNoDefendingUnits() {
        $unitsToAttack = $this->createAtackingUnits(1000, 1000);
        $defender      = $this->createEnemyKingdom()->getKingdom();

        foreach ($defender->units as $unit) {
            $unit->delete();
        }

        $defender = $defender->refresh();
        
        $unitAttackHandler = new UnitHandler();

        $unitsLeft = $unitAttackHandler->attack($defender, $unitsToAttack);

        $walls = $defender->refresh()->buildings->where('name', 'Walls')->first();

        $this->assertEquals(0, $walls->current_durability);
        
        $this->assertTrue($unitsLeft[0]['amount'] > 0);
    }

    protected function createAtackingUnits(int $totalAttack = 100, int $totalDefence = 100): array {
        return [
            [
                "amount"         => 10,
                "total_attack"   => $totalAttack,
                "total_defence"  => $totalDefence,
                "unit_id"        => 1,
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