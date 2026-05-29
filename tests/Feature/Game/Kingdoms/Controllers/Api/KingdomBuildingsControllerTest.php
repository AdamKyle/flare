<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Values\AutomationType;
use App\Game\Kingdoms\Values\BuildingQueueType;
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

        $this->actingAs($character->user);
        $response = $this->call(
            'POST',
            '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'to_level' => $building->level + 1,
            ])
        );

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

        $this->actingAs($character->user);
        $response = $this->call(
            'POST',
            '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'to_level' => 2,
            ])
        );

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

        $this->actingAs($character->user);
        $response = $this->call(
            'POST',
            '/api/kingdoms/' . $character->id . '/upgrade-building/' . $building->id,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'from_level' => 2,
                'to_level' => 4,
            ])
        );

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
