<?php

namespace Tests\Unit\Game\Kingdoms\Handlers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Flare\Models\Kingdom;
use App\Game\Kingdoms\Handlers\SiegeHandler;
use Tests\TestCase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\KingdomManagement;

class SiegeHandlerTest extends TestCase {

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
                                                 ], 1000)
                                                ->assignUnits([
                                                    'can_heal'        => true,
                                                    'heal_percentage' => 0.01
                                                ], 1000);
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testAttackKingdomAllBuildingsHaveFallen() {
        $unitsToAttack = $this->createAtackingUnits();
        $defender      = $this->createEnemyKingdom()->getKingdom();

        $siegeHandler  = resolve(SiegeHandler::class);

        $siegeUnits = $siegeHandler->attack($defender, $unitsToAttack, []);

        $defender  = $defender->refresh();
        $buildings = $defender->buildings;
        $units     = $defender->units;

        foreach ($buildings as $building) {
            $this->assertEquals(0, $building->current_durability);
        }

        foreach($units as $unit) {
            $this->assertEquals(0, $unit->amount);
        }

        // We lost at least one.
        $this->assertEquals(9.0, $siegeUnits[0]['amount']);
    }

    public function testAttackKingdomCantGetPastTheWalls() {
        $unitsToAttack = $this->createAtackingUnits();
        $defender      = $this->createEnemyKingdom()->getKingdom();


        foreach ($unitsToAttack as $index => $unitInfo) {
            $unitsToAttack[$index]['amount']        = 1;
            $unitsToAttack[$index]['total_attack']  = 1;
            $unitsToAttack[$index]['total_defence'] = 1;
        }

        $siegeHandler  = resolve(SiegeHandler::class);

        $siegeUnits = $siegeHandler->attack($defender, $unitsToAttack, []);

        // We lost all units
        foreach($siegeUnits as $index => $unitInfo) {
            $this->assertEquals(0, $siegeUnits[$index]['amount']);
        }
    }

    public function testAttackKingdomAllBuildingsHaveFallenWithSiegeWeapons() {
        $unitsToAttack = $this->createAtackingUnits();
        $defender      = $this->createEnemyKingdom()->assignUnits([
            'siege_weapon' => true,
            'attack'       => 3000,
            'defence'      => 1000,
            'defender'     => true,
        ], 2000)->getKingdom();

        $siegeHandler  = resolve(SiegeHandler::class);

        $siegeHandler->attack($defender, $unitsToAttack, []);

        $defender  = $defender->refresh();
        $buildings = $defender->buildings;

        foreach ($buildings as $building) {
            $this->assertEquals(0, $building->current_durability);
        }
    }

    public function testAttackKingdomWhereDefenderHasSiegeUnits() {
        $unitsToAttack = $this->createAtackingUnits();
        $defender      = $this->createEnemyKingdom()->assignUnits([
            'siege_weapon' => true,
            'attack'       => 3000,
            'defence'      => 1000,
            'defender'     => true,
        ], 2000)->getKingdom();

        $siegeHandler  = resolve(SiegeHandler::class);

        $defender->buildings->where('is_farm', false)->where('is_walls', false)->first()->update([
            'current_durability' => 20000,
            'current_defence'    => 20000
        ]);

        $defender = $defender->refresh();

        $siegeHandler->attack($defender, $unitsToAttack, []);

        $defender  = $defender->refresh();
        $farm      = $defender->buildings->where('is_farm', true)->first();
        $walls     = $defender->buildings->where('is_walls', true)->first();

        $this->assertEquals(0, $farm->current_durability);
        $this->assertEquals(0, $walls->current_durability);

        $buildingThatHasntFallen = $defender->buildings->where('current_durability', '>', 0)->first();

        $this->assertNotEquals(0, $buildingThatHasntFallen->current_durability);
    }

    public function testAttackKingdomsWithNoDefence() {
        $unitsToAttack = $this->createAtackingUnits();
        $defender      = $this->createEnemyKingdom()->getKingdom();

        foreach ($defender->buildings as $building) {
            $building->update([
                'current_durability' => 0,
            ]);
        }

        foreach ($defender->units as $unit) {
            $unit->delete();
        }

        $defender = $defender->refresh();

        $siegeHandler  = resolve(SiegeHandler::class);

        $unitsToAttack = $siegeHandler->attack($defender, $unitsToAttack, []);

        foreach ($unitsToAttack as $unitsInfo) {
            $this->assertEquals(10, $unitsInfo['amount']);
        }
    }

    public function testDefenderUnitsGetHealed() {
        $unitsToAttack = $this->createAtackingUnits();
        $defender      = $this->createEnemyKingdom()->assignUnits([
            'can_heal' => true,
            'heal_percentage' => 0.01
        ], 50000)->getKingdom();

        $siegeHandler  = resolve(SiegeHandler::class);

        $siegeHandler->attack($defender, $unitsToAttack, []);

        $defender = $defender->refresh();

        $attackingUnit = $defender->units->first();
        $healingUnit   = $defender->units()->orderBy('id', 'desc')->first();

        $this->assertEquals(500, $attackingUnit->amount);
        $this->assertEquals(50000, $healingUnit->amount);
    }

    protected function createAtackingUnits(): array {
        return [
            [
                "amount"         => 10,
                "total_attack"   => 5000,
                "total_defence"  => 5000,
                "primary_target" => 'Walls',
                "fall_back"      => 'Farm',
                "unit_id"        => 1,
            ],
            [
                "amount"         => 10,
                "total_attack"   => 5000,
                "total_defence"  => 5000,
                "primary_target" => 'Walls',
                "fall_back"      => 'Farm',
                "unit_id"        => 2,
            ],
            [
                "amount"         => 10,
                "total_attack"   => 5000,
                "total_defence"  => 5000,
                "primary_target" => 'Walls',
                "fall_back"      => 'Buildings',
                "unit_id"        => 3,
            ],
        ];
    }

    public function createHealingUnits(): array {

        return [
            [
                'amount'   => 20,
                'heal_for' => 0.10,
                'unit_id'  => 4,
            ]
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
