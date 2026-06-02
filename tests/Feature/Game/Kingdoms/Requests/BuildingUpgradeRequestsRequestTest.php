<?php

namespace Tests\Feature\Game\Kingdoms\Requests;

use App\Flare\Models\BuildingInQueue;
use App\Game\Kingdoms\Jobs\CapitalCityQueueUpBuildingRequests;
use App\Game\Kingdoms\Requests\BuildingUpgradeRequestsRequest;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class BuildingUpgradeRequestsRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testRequestDoesNotHaveBusinessValidation(): void
    {
        $this->assertFalse(method_exists(BuildingUpgradeRequestsRequest::class, 'withValidator'));
    }

    public function testCapitalCityUpgradeRejectsDamagedBuilding(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->getKingdom();
        $targetKingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([], [
                'current_durability' => 99,
                'max_durability' => 100,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'One or more buildings must be repaired before they can be upgraded.',
        ]);
        Queue::assertNotPushed(CapitalCityQueueUpBuildingRequests::class);
    }

    public function testCapitalCityRepairRejectsManuallyQueuedBuilding(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->getKingdom();
        $targetKingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([], [
                'current_durability' => 1,
                'max_durability' => 100,
            ]);
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'building_id' => $building->id,
            'to_level' => $building->level,
            'type' => BuildingQueueType::REPAIR,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_type' => 'repair',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'One or more buildings are already queued.',
        ]);
        Queue::assertNotPushed(CapitalCityQueueUpBuildingRequests::class);
    }

    public function testCapitalCityRepairRejectsCapitalCityQueuedBuilding(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->getKingdom();
        $targetKingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([], [
                'current_durability' => 1,
                'max_durability' => 100,
            ]);
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
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
            'completed_at' => now()->addMinutes(10),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_type' => 'repair',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'One or more buildings are already queued.',
        ]);
        Queue::assertNotPushed(CapitalCityQueueUpBuildingRequests::class);
    }

    public function testCapitalCityRepairAllowsCancellationRejectedCapitalCityQueuedBuilding(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->getKingdom();
        $targetKingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([], [
                'current_durability' => 1,
                'max_durability' => 100,
            ]);
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'repair',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::CANCELLATION_REJECTED,
                'from_level' => null,
                'to_level' => null,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::CANCELLATION_REJECTED,
            'started_at' => now(),
            'completed_at' => now()->subMinute(),
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_type' => 'repair',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ]);

        $response->assertOk();
        Queue::assertPushed(CapitalCityQueueUpBuildingRequests::class);
    }


    public function testCapitalCityValidNonQueuedUpgradeDispatchesRequest(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->getKingdom();
        $targetKingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding()
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ]);

        $response->assertOk();
        Queue::assertPushed(CapitalCityQueueUpBuildingRequests::class);
    }

    public function testCapitalCityValidNonQueuedRepairDispatchesRequest(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->getKingdom();
        $targetKingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 2000,
                'current_clay' => 2000,
                'current_stone' => 2000,
                'current_iron' => 2000,
                'current_population' => 2000,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([], [
                'current_durability' => 1,
                'max_durability' => 100,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_type' => 'repair',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ]);

        $response->assertOk();
        Queue::assertPushed(CapitalCityQueueUpBuildingRequests::class);
    }
}