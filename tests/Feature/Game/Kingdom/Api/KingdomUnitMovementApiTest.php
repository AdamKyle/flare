<?php

namespace Tests\Feature\Game\Kingdom\Api;

use App\Flare\Models\Character;
use App\Flare\Models\Kingdom;
use App\Flare\Models\UnitMovementQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\KingdomManagement;
use Tests\TestCase;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateUnitMovementQueue;

class KingdomUnitMovementApiTest extends TestCase
{
    use RefreshDatabase, CreateUnitMovementQueue, CreateKingdom;

    private $character;

    public function setUp(): void {
        parent::setUp();

        $this->character = $this->createKingdom();
    }

    public function tearDown(): void {
        parent::tearDown();

        $this->character = null;
    }

    public function testFetchUnitsInMovement() {
        $attacker = $this->createKingdom()->assignUnits([
            'attack'  => 5000,
            'defence' => 5000,
            'siege_weapon' => true,
        ], 500)->assignUnits([
            'is_settler'        => true,
            'reduces_morale_by' => 0.10
        ]);

        $this->createUnitMovement($this->character->getKingdom(), $attacker->getKingdom());

        $response = $this->actingAs($this->character->getUser())->json('GET', route('kingdom.unit.movement', [
            'character' => $this->character->getCharacter()->id,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content);
    }

    public function testFetchNewUnitsInMovement() {
        $attacker = $this->createKingdom()->assignUnits([
            'attack'  => 5000,
            'defence' => 5000,
            'siege_weapon' => true,
        ], 500)->assignUnits([
            'is_settler'        => true,
            'reduces_morale_by' => 0.10
        ]);

        $this->createUnitMovementQueue([
            'character_id'        => Character::first()->id,
            'from_kingdom_id'    => $attacker->getKingdom()->id,
            'to_kingdom_id'      => $this->character->getKingdom()->id,
            'units_moving'       => [
                'new_units' => $this->getUnitsInMovement($attacker->getKingdom()),
                'old_units' => [],
            ],
            'completed_at'       => now()->addMinutes(10),
            'started_at'         => now()->addMinutes(1),
            'moving_to_x'        => 16,
            'moving_to_y'        => 16,
            'from_x'             => 32,
            'from_y'             => 32,
            'is_recalled'        => false,
            'is_returning'       => false,
        ]);

        $response = $this->actingAs($this->character->getUser())->json('GET', route('kingdom.unit.movement', [
            'character' => $this->character->getCharacter()->id,
        ]))->response;

        $content = json_decode($response->content());
        $this->assertEquals(200, $response->status());
        $this->assertNotEmpty($content);
    }

    public function testCanRecallUnits() {
        $attacker = $this->createKingdom()->assignUnits([
            'attack'  => 5000,
            'defence' => 5000,
            'siege_weapon' => true,
        ], 500)->assignUnits([
            'is_settler'        => true,
            'reduces_morale_by' => 0.10
        ]);

        $unitMovementQueue = $this->createUnitMovement($this->character->getKingdom(), $attacker->getKingdom());

        $response = $this->actingAs($this->character->getUser())->json('POST', route('recall.units', [
            'character'         => $this->character->getCharacter()->id,
            'unitMovementQueue' => $unitMovementQueue->id,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEmpty($content);
    }

    public function testCannotRecallUnits() {
        $attacker = $this->createKingdom()->assignUnits([
            'attack'  => 5000,
            'defence' => 5000,
            'siege_weapon' => true,
        ], 500)->assignUnits([
            'is_settler'        => true,
            'reduces_morale_by' => 0.10
        ]);

        $unitMovementQueue = $this->createUnitMovement($this->character->getKingdom(), $attacker->getKingdom(), 10, 11);

        $response = $this->actingAs($this->character->getUser())->json('POST', route('recall.units', [
            'character'         => $this->character->getCharacter()->id,
            'unitMovementQueue' => $unitMovementQueue->id,
        ]))->response;

        $content = json_decode($response->content());

        $this->assertEquals(200, $response->status());
        $this->assertEquals('You\'re units are too close to their destination.', $content->message);
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

    protected function createUnitMovement(Kingdom $defenderKingdom, Kingdom $attackingKingdom, $completeInMinutes = 100, $startedAtMinutes = 0): UnitMovementQueue {
        return $this->createUnitMovementQueue([
            'character_id'       => Character::first()->id,
            'from_kingdom_id'    => $attackingKingdom->id,
            'to_kingdom_id'      => $defenderKingdom->id,
            'units_moving'       => $this->getUnitsInMovement($attackingKingdom),
            'completed_at'       => now()->addMinutes($completeInMinutes),
            'started_at'         => now()->addMinutes($startedAtMinutes),
            'moving_to_x'        => 16,
            'moving_to_y'        => 16,
            'from_x'             => 32,
            'from_y'             => 32,
            'is_recalled'        => false,
            'is_returning'       => false,
        ]);
    }

    private function getUnitsInMovement(Kingdom $kingdom): array {
        $unitsToSend = [];

        foreach ($kingdom->units as $unit) {
            $unitsToSend[] = [
                'unit_id'        => $unit->game_unit_id,
                'amount'         => $unit->amount,
                'time_to_return' => 1,
            ];
        }

        return $unitsToSend;
    }
}
