<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameUnit;
use App\Flare\Models\UnitInQueue;
use App\Flare\Values\AutomationType;
use App\Game\Kingdoms\Jobs\CapitalCityQueueUpBuildingRequests;
use App\Game\Kingdoms\Jobs\CapitalCityQueueUpUnitRequests;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function testDirectApiCannotQueueMaxLevelBuilding(): void
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
            ->assignBuilding([
                'max_level' => 1,
            ], [
                'level' => 1,
            ]);
        $targetKingdom = $targetKingdomManagement->getKingdom();
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
        $this->assertSame(0, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $targetKingdom->refresh()->current_wood);
        $this->assertSame(2000, $targetKingdom->refresh()->current_clay);
        $this->assertSame(2000, $targetKingdom->refresh()->current_stone);
        $this->assertSame(2000, $targetKingdom->refresh()->current_iron);
        $this->assertSame(2000, $targetKingdom->refresh()->current_population);
    }

    public function testDirectApiRejectsBuildingAlreadyQueuedManually(): void
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
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
            ]);
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'building_id' => $building->id,
            'from_level' => 1,
            'to_level' => 2,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);

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
        $this->assertSame(0, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $targetKingdom->refresh()->current_wood);
    }

    public function testDirectApiRejectsBuildingAlreadyQueuedByCapitalCity(): void
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
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
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
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
                'from_level' => 1,
                'to_level' => 2,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);

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
        $this->assertSame(1, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $targetKingdom->refresh()->current_wood);
    }

    public function testDirectApiRejectsUnitRequestsThatWouldExceedMaxBeforeDispatch(): void
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
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignUnits([], KingdomMaxValue::MAX_UNIT - 10)
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $gameUnit = $targetKingdom->units()->first()->gameUnit;

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/recruit-unit-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_data' => [[
                    'kingdom_id' => $targetKingdom->id,
                    'unit_requests' => [
                        [
                            'unit_name' => $gameUnit->name,
                            'unit_amount' => 11,
                        ],
                    ],
                ]],
            ]);

        $response->assertOk();
        Queue::assertPushed(CapitalCityQueueUpUnitRequests::class);
        $this->assertSame(0, CapitalCityUnitQueue::where('kingdom_id', $targetKingdom->id)->count());
    }

    public function testCapitalCityBuildingQueueRejectsDuringAutomation(): void
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
        $targetKingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
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
        Queue::assertNotPushed(CapitalCityQueueUpBuildingRequests::class);
        $this->assertSame(0, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
    }

    public function testCapitalCityUnitQueueRejectsDuringAutomation(): void
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
        $targetKingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignUnits([], 1)
            ->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();
        $gameUnit = $targetKingdom->units()->first()->gameUnit;

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/recruit-unit-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_data' => [[
                    'kingdom_id' => $targetKingdom->id,
                    'unit_requests' => [[
                        'unit_name' => $gameUnit->name,
                        'unit_amount' => 1,
                    ]],
                ]],
            ]);

        $response->assertStatus(422);
        Queue::assertNotPushed(CapitalCityQueueUpUnitRequests::class);
        $this->assertSame(0, CapitalCityUnitQueue::where('kingdom_id', $targetKingdom->id)->count());
    }

    public function testCapitalCityBuildingCancelRejectsDuringAutomation(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCityManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
            ])
            ->assignBuilding();
        $capitalCity = $capitalCityManagement->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();
        $building = $capitalCity->buildings()->first();
        $capitalCityManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $capitalCity->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
                'from_level' => 1,
                'to_level' => 2,
                'type' => 'upgrade',
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $capitalCityBuildingQueue = $capitalCityManagement->getCapitalCityBuildingQueue();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/cancel-building-request/' . $character->id . '/' . $capitalCity->id, [
                'queue_id' => $capitalCityBuildingQueue->id,
            ]);

        $response->assertStatus(422);
        $this->assertNotNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
    }

    public function testCapitalCityUnitCancelRejectsDuringAutomation(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCityManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
            ])
            ->assignUnits([], 1);
        $capitalCity = $capitalCityManagement->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();
        $gameUnit = $capitalCity->units()->first()->gameUnit;
        $capitalCityManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $capitalCity->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $gameUnit->name,
                'amount' => 1,
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $capitalCityUnitQueue = $capitalCityManagement->getCapitalCityUnitQueue();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/cancel-unit-request/' . $character->id . '/' . $capitalCity->id, [
                'queue_id' => $capitalCityUnitQueue->id,
            ]);

        $response->assertStatus(422);
        $this->assertNotNull(CapitalCityUnitQueue::find($capitalCityUnitQueue->id));
    }

    public function testCapitalCityBuildingRequestDispatchesOnLongRunningConnection(): void
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
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
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

        $response->assertOk();
        Queue::assertPushed(CapitalCityQueueUpBuildingRequests::class, function (CapitalCityQueueUpBuildingRequests $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }

    public function testCapitalCityUnitRequestDispatchesOnLongRunningConnection(): void
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
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $gameUnit = GameUnit::factory()->create();

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/recruit-unit-requests/' . $character->id . '/' . $capitalCity->id, [
                'request_data' => [[
                    'kingdom_id' => $targetKingdom->id,
                    'unit_requests' => [[
                        'unit_name' => $gameUnit->name,
                        'unit_amount' => 1,
                    ]],
                ]],
            ]);

        $response->assertOk();
        Queue::assertPushed(CapitalCityQueueUpUnitRequests::class, function (CapitalCityQueueUpUnitRequests $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }
}
