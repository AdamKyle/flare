<?php

namespace Tests\Feature\Game\Kingdom;

use App\Flare\Models\Kingdom;
use App\Flare\Models\KingdomLog;
use App\Flare\Models\UnitMovementQueue;
use App\Flare\Values\KingdomLogStatusValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\Setup\Character\KingdomManagement;
use Tests\TestCase;
use Tests\Traits\CreateKingdom;
use Tests\Traits\CreateUnitMovementQueue;

class KingdomControllerTest extends TestCase {

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

    public function testVisitUnitMovement() {
        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.unit-movement', [
            'character' => $this->character->getCharacter()->id
        ]))->see('Units In Movement');
    }

    public function testVisitKingdomLogs() {
        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-logs', [
            'character' => $this->character->getCharacter()->id
        ]))->see('Attack Logs');
    }

    public function testVisitKingdomLog() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter()->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::KINGDOM_ATTACKED,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-log', [
            'character'  => $this->character->getCharacter()->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::KINGDOM_ATTACKED . ')');
    }

    public function testVisitKingdomLogForAttacked() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter()->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::ATTACKED,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-log', [
            'character'  => $this->character->getCharacter()->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::ATTACKED . ')');
    }

    public function testVisitKingdomLogForLostAttack() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter()->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::LOST,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-log', [
            'character'  => $this->character->getCharacter()->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::LOST . ')');
    }

    public function testVisitKingdomLogForTakenKingdom() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter()->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::TAKEN,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-log', [
            'character'  => $this->character->getCharacter()->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::TAKEN . ')');
    }

    public function testKingdomMoraleReduced() {

        $newKingdom = $this->character->getKingdom()->load('buildings', 'units');

        foreach ($newKingdom->buildings as $building) {
            $building->update([
                'current_durability' => 0
            ]);
        }

        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter()->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::KINGDOM_ATTACKED,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $newKingdom->refresh()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-log', [
            'character'  => $this->character->getCharacter()->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::KINGDOM_ATTACKED . ')');
    }

    public function testDeleteLog() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter()->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::KINGDOM_ATTACKED,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $this->actingAs($this->character->getUser())->post(route('game.kingdom.delete-log', [
            'character'  => $this->character->getCharacter()->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]));

        $this->assertTrue(KingdomLog::all()->isEmpty());
    }

    public function testBatchDeleteLogs() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter()->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::KINGDOM_ATTACKED,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $this->actingAs($this->character->getUser())->post(route('game.kingdom.batch-delete-logs', [
            'character'  => $this->character->getCharacter()->id,
        ]), [
            'logs' => [KingdomLog::first()->id]
        ]);

        $this->assertTrue(KingdomLog::all()->isEmpty());
    }

    public function testBatchDeleteEmptyLogs() {
        $response = $this->actingAs($this->character->getUser())->post(route('game.kingdom.batch-delete-logs', [
            'character'  => $this->character->getCharacter()->id,
        ]), [
            'logs' => [1]
        ])->response;

        $response->assertSessionHas('error', "No logs exist for selected.");
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
}
