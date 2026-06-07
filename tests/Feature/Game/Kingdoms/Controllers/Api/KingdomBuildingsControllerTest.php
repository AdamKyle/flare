<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
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

    public function testManualCancelRejectsCapitalCityOwnedBuildingQueue(): void
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

    public function testManualUpgradeRejectsDuringAutomation(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
                'to_level' => $building->level + 1,
            ]);

        $response->assertStatus(422);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)->count());
    }

    public function testManualRebuildRejectsDuringAutomation(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/rebuild-building/' . $building->id);

        $response->assertStatus(422);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)->count());
    }

    public function testManualBuildingCancelRejectsDuringAutomation(): void
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

    public function testManualUpgradeReturnsValidationErrorWhenActiveDuplicateUpgradeQueueExists(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
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

    public function testManualUpgradeReturnsValidationErrorWhenCapitalCityQueueExistsForBuilding(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
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

    public function testManualRepairReturnsValidationErrorWhenCapitalCityQueueExistsForBuilding(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/rebuild-building/' . $building->id);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'Building is already in the process of upgrading.',
        ]);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)
            ->where('building_id', $building->id)
            ->count());
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
    }

    public function testManualUpgradeReturnsValidationErrorWhenBuildingIsDamaged(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
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

    public function testRawAuthenticatedJsonRequestRejectsDuplicateManualUpgradeQueue(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
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

    public function testPendingUpgradeQueueBlocksAnotherManualQueueForSameBuilding(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
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

    public function testBuildingCanBeQueuedAgainAfterExistingUpgradeQueueIsRemoved(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
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

    public function testRawAuthenticatedJsonRequestCannotQueueMaxLevelBuilding(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
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

    public function testManualEndpointCannotQueueMaxLevelBuilding(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
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

    public function testQueueWithToLevelAboveMaxIsRejectedAndDoesNotMutateBuilding(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
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

    public function testQueueCreationStoresFromLevelAndToLevel(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
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

    public function testRawAuthenticatedJsonRequestCannotForceInvalidFromLevelOrToLevel(): void
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
            ->call('POST', '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id, [
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

    public function testNonOwnerCannotUpgradeBuildingThatBelongsToAnotherCharacter(): void
    {
        Queue::fake();

        $ownerFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $ownerFactory
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
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $nonOwner = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $response = $this->actingAs($nonOwner->user)
            ->call('POST', '/api/kingdoms/' . $nonOwner->id . '/upgrade-building/' . $building->id, [
                'to_level' => 2,
            ], [], [], ['HTTP_ACCEPT' => 'application/json']);

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Nope. Not allowed to do that.']);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
        $this->assertSame(2000, $kingdom->refresh()->current_clay);
        $this->assertSame(2000, $kingdom->refresh()->current_stone);
        $this->assertSame(2000, $kingdom->refresh()->current_iron);
        $this->assertSame(2000, $kingdom->refresh()->current_population);
    }

    public function testNonOwnerCannotRebuildBuildingThatBelongsToAnotherCharacter(): void
    {
        Queue::fake();

        $ownerFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $ownerFactory
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
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $nonOwner = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $response = $this->actingAs($nonOwner->user)
            ->call('POST', '/api/kingdoms/' . $nonOwner->id . '/rebuild-building/' . $building->id,
                [], [], [], ['HTTP_ACCEPT' => 'application/json']
            );

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Nope. Not allowed to do that.']);
        $this->assertSame(0, BuildingInQueue::where('kingdom_id', $kingdom->id)->count());
        $this->assertSame(1, $building->refresh()->current_durability);
        $this->assertSame(2000, $kingdom->refresh()->current_wood);
        $this->assertSame(2000, $kingdom->refresh()->current_clay);
        $this->assertSame(2000, $kingdom->refresh()->current_stone);
        $this->assertSame(2000, $kingdom->refresh()->current_iron);
        $this->assertSame(2000, $kingdom->refresh()->current_population);
    }

    public function testNonOwnerCannotCancelAnotherCharactersQueueEntry(): void
    {
        $ownerFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $ownerFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $owner = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $queue = BuildingInQueue::factory()->create([
            'character_id' => $owner->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'to_level' => $building->level + 1,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $nonOwner = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation()
            ->getCharacter();

        $response = $this->actingAs($nonOwner->user)
            ->call('POST', '/api/kingdoms/building-upgrade/cancel', [
                'queue_id' => $queue->id,
            ]);

        $response->assertStatus(422);
        $response->assertJson(['message' => 'You do not own that queue.']);
        $this->assertNotNull(BuildingInQueue::find($queue->id));
        $this->assertSame($building->level, $building->refresh()->level);
        $this->assertSame($kingdom->current_wood, $kingdom->refresh()->current_wood);
        $this->assertSame($kingdom->current_clay, $kingdom->refresh()->current_clay);
        $this->assertSame($kingdom->current_stone, $kingdom->refresh()->current_stone);
        $this->assertSame($kingdom->current_iron, $kingdom->refresh()->current_iron);
        $this->assertSame($kingdom->current_population, $kingdom->refresh()->current_population);
    }
}
