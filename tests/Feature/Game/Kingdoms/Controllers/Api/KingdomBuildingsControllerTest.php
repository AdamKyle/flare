<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Values\AutomationType;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomBuildingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_cancel_rejects_capital_city_owned_building_queue(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => $building->level + 1,
            'type' => BuildingQueueType::UPGRADE,
            'capital_city_building_queue_id' => 123,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/building-upgrade/cancel', [
                'queue_id' => $queue->id,
            ]);

        $response->assertStatus(422);
        $this->assertNotNull(BuildingInQueue::find($queue->id));
    }

    public function test_manual_upgrade_rejects_during_automation(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => $building->level + 1,
            ]);

        $response->assertStatus(422);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)->count());
    }

    public function test_manual_rebuild_rejects_during_automation(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding([], [
                'current_durability' => 1,
                'max_durability' => 100,
            ]);
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/rebuild-building/'.$building->id);

        $response->assertStatus(422);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)->count());
    }

    public function test_manual_building_cancel_rejects_during_automation(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => $building->level + 1,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/building-upgrade/cancel', [
                'queue_id' => $queue->id,
            ]);

        $response->assertStatus(422);
        $this->assertNotNull(BuildingInQueue::find($queue->id));
    }

    public function test_manual_upgrade_returns_validation_error_when_active_duplicate_upgrade_queue_exists(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding();
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => $building->level + 1,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => $building->level + 1,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Building is already in the process of upgrading.',
        ]);
        $this->assertSame(1, BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->where('type', BuildingQueueType::UPGRADE)
            ->count());
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
        $this->assertSame(2000, $kingdom->refresh()->current_clay);
        $this->assertSame(2000, $kingdom->refresh()->current_stone);
        $this->assertSame(2000, $kingdom->refresh()->current_iron);
    }

    public function test_manual_upgrade_returns_validation_error_when_capital_city_queue_exists_for_building(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
            ])
            ->getKingdom();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding();
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $kingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => $building->level + 1,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Building is already in the process of upgrading.',
        ]);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->count());
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
    }

    public function test_manual_repair_returns_validation_error_when_capital_city_queue_exists_for_building(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
            ])
            ->getKingdom();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding([], [
                'current_durability' => 1,
                'max_durability' => 100,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $kingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'repair',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::REPAIRING,
                'from_level' => null,
                'to_level' => null,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REPAIRING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/rebuild-building/'.$building->id);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Building is already in the process of upgrading.',
        ]);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->count());
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
    }

    public function test_manual_upgrade_returns_validation_error_when_building_is_damaged(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding([], [
                'current_durability' => 99,
                'max_durability' => 100,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => $building->level + 1,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Building must be repaired before it can be upgraded.',
        ]);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->count());
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
    }

    public function test_raw_authenticated_json_request_rejects_duplicate_manual_upgrade_queue(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding();
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => $building->level + 1,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => $building->level + 1,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Building is already in the process of upgrading.',
        ]);
        $this->assertSame(1, BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->where('type', BuildingQueueType::UPGRADE)
            ->count());
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
    }

    public function test_pending_upgrade_queue_blocks_another_manual_queue_for_same_building(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding();
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => $building->level + 1,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => $building->level + 1,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Building is already in the process of upgrading.',
        ]);
        $this->assertSame(1, BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->where('type', BuildingQueueType::UPGRADE)
            ->count());
    }

    public function test_building_can_be_queued_again_after_existing_upgrade_queue_is_removed(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding();
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => $building->level + 1,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subMinutes(30),
            'completed_at' => now()->addMinutes(30),
        ]);
        $queue->delete();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => $building->level + 1,
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Building is in the process of upgrading!',
        ]);
        $this->assertSame(1, BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->where('type', BuildingQueueType::UPGRADE)
            ->count());
    }

    public function test_raw_authenticated_json_request_cannot_queue_max_level_building(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding([
                'max_level' => 1,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => 2,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Building is already max level.',
        ]);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->where('type', BuildingQueueType::UPGRADE)
            ->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
    }

    public function test_manual_endpoint_cannot_queue_max_level_building(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding([
                'max_level' => 1,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => 2,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Building is already max level.',
        ]);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->where('type', BuildingQueueType::UPGRADE)
            ->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
    }

    public function test_queue_with_to_level_above_max_is_rejected_and_does_not_mutate_building(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding([
                'max_level' => 2,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => 3,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Building is already max level.',
        ]);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->where('type', BuildingQueueType::UPGRADE)
            ->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
    }

    public function test_queue_creation_stores_from_level_and_to_level(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'to_level' => 2,
            ]);

        $response->assertStatus(200);
        $queue = BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->where('type', BuildingQueueType::UPGRADE)
            ->first();

        $this->assertNotNull($queue);
        $this->assertSame(1, $queue->from_level);
        $this->assertSame(2, $queue->to_level);
    }

    public function test_raw_authenticated_json_request_cannot_force_invalid_from_level_or_to_level(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
            ])
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdoms/'.$character->id.'/upgrade-building/'.$building->id, [
                'from_level' => 2,
                'to_level' => 4,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Invalid building upgrade request.',
        ]);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->where('type', BuildingQueueType::UPGRADE)
            ->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
    }
}
