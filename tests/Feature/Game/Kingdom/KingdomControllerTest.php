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
            'character' => $this->character->getCharacter(false)->id
        ]))->see('Units In Movement');
    }

    public function testVisitKingdomLogs() {
        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-logs', [
            'character' => $this->character->getCharacter(false)->id
        ]))->see('Attack Logs');
    }

    public function testVisitKingdomLog() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
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
            'character'  => $this->character->getCharacter(false)->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::KINGDOM_ATTACKED . ')');
    }

    public function testVisitKingdomLogNoUnitsSent() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::KINGDOM_ATTACKED,
            'units_sent'      => null,
            'units_survived'  => [],
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-log', [
            'character'  => $this->character->getCharacter(false)->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::KINGDOM_ATTACKED . ')');
    }

    public function testVisitKingdomLogAllUnitsSurvived() {

        $kingdomWithUnits     = $this->character->getKingdom()->load('units');
        $unitsSent            = [];

        foreach ($kingdomWithUnits->units as $unit) {
            $unitsSent[] = [
                'unit_id' => $unit->gameUnit->id,
                'amount'  => 10,
            ];
        }

        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::KINGDOM_ATTACKED,
            'units_sent'      => $unitsSent,
            'units_survived'  => $unitsSent,
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-log', [
            'character'  => $this->character->getCharacter(false)->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::KINGDOM_ATTACKED . ')');
    }

    public function testVisitKingdomLogNoUnitsSurvived() {

        $kingdomWithUnits     = $this->character->getKingdom()->load('units');
        $unitsSent            = [];
        $unitsSurvived        = [];

        foreach ($kingdomWithUnits->units as $unit) {
            $unitsSent[] = [
                'unit_id' => $unit->gameUnit->id,
                'amount'  => 10,
            ];

            $unitsSurvived[] = [
                'unit_id' => $unit->gameUnit->id,
                'amount'  => 0,
            ];
        }

        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::KINGDOM_ATTACKED,
            'units_sent'      => $unitsSent,
            'units_survived'  => $unitsSurvived,
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-log', [
            'character'  => $this->character->getCharacter(false)->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::KINGDOM_ATTACKED . ')');
    }

    public function testVisitKingdomLogSomeUnitsSurvived() {

        $kingdomWithUnits     = $this->character->getKingdom()->load('units');
        $unitsSent            = [];
        $unitsSurvived        = [];

        foreach ($kingdomWithUnits->units as $unit) {
            $unitsSent[] = [
                'unit_id' => $unit->gameUnit->id,
                'amount'  => 10,
            ];

            $unitsSurvived[] = [
                'unit_id' => $unit->gameUnit->id,
                'amount'  => 5,
            ];
        }

        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::KINGDOM_ATTACKED,
            'units_sent'      => $unitsSent,
            'units_survived'  => $unitsSurvived,
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-log', [
            'character'  => $this->character->getCharacter(false)->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::KINGDOM_ATTACKED . ')');
    }

    public function testVisitKingdomLogForAttacked() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
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
            'character'  => $this->character->getCharacter(false)->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::ATTACKED . ')');
    }

    public function testVisitKingdomLogForAttackedWithNoBuildingsLeft() {
        $log = $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::ATTACKED,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $kingdom = $this->character->getKingdom();

        $kingdom->buildings()->update([
            'current_durability' => 0
        ]);

        $log->update([
            'new_defender' => $kingdom->refresh()->load('buildings', 'units')->toArray()
        ]);

        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-log', [
            'character'  => $this->character->getCharacter(false)->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::ATTACKED . ')');
    }

    public function testVisitKingdomLogForAttackedNoUnitsSurvived() {
        $log = $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
            'from_kingdom_id' => $this->character->getKingdom()->id,
            'to_kingdom_id'   => $this->character->getKingdom()->id,
            'status'          => KingdomLogStatusValue::ATTACKED,
            'units_sent'      => [],
            'units_survived'  => [],
            'old_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'new_defender'    => $this->character->getKingdom()->load('buildings', 'units')->toArray(),
            'published'       => true,
        ]);

        $kingdom = $this->character->getKingdom();

        $kingdom->units()->update([
            'amount' => 0
        ]);

        $log->update([
            'new_defender' => $kingdom->refresh()->load('buildings', 'units')->toArray()
        ]);

        $this->actingAs($this->character->getUser())->visit(route('game.kingdom.attack-log', [
            'character'  => $this->character->getCharacter(false)->id,
            'kingdomLog' => $log->refresh()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::ATTACKED . ')');
    }

    public function testVisitKingdomLogForLostAttack() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
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
            'character'  => $this->character->getCharacter(false)->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::LOST . ')');
    }

    public function testVisitKingdomLogForTakenKingdom() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
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
            'character'  => $this->character->getCharacter(false)->id,
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
            'character_id'    => $this->character->getCharacter(false)->id,
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
            'character'  => $this->character->getCharacter(false)->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]))->see('Attack Log (' . KingdomLogStatusValue::KINGDOM_ATTACKED . ')');
    }

    public function testDeleteLog() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
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
            'character'  => $this->character->getCharacter(false)->id,
            'kingdomLog' => KingdomLog::first()->id,
        ]));

        $this->assertTrue(KingdomLog::all()->isEmpty());
    }

    public function testBatchDeleteLogs() {
        $this->createKingdomLog([
            'character_id'    => $this->character->getCharacter(false)->id,
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
            'character'  => $this->character->getCharacter(false)->id,
        ]), [
            'logs' => [KingdomLog::first()->id]
        ]);

        $this->assertTrue(KingdomLog::all()->isEmpty());
    }

    public function testBatchDeleteEmptyLogs() {
        $response = $this->actingAs($this->character->getUser())->post(route('game.kingdom.batch-delete-logs', [
            'character'  => $this->character->getCharacter(false)->id,
        ]), [
            'logs' => [1]
        ])->response;

        $response->assertSessionHas('error', "No log exists for your selection.");
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
