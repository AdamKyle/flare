<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitQueue;
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

    public function test_direct_api_cannot_queue_max_level_building(): void
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
            ->assignBuilding([
                'max_level' => 1,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        $this->actingAs($character->user);
        $response = $this->call(
            'POST',
            '/api/kingdom/capital-city/upgrade-building-requests/'.$character->id.'/'.$capitalCity->id,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ])
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'One or more buildings are already max level.',
        ]);
        $this->assertSame(0, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $targetKingdom->refresh()->current_wood);
        $this->assertSame(2000, $targetKingdom->refresh()->current_clay);
        $this->assertSame(2000, $targetKingdom->refresh()->current_stone);
        $this->assertSame(2000, $targetKingdom->refresh()->current_iron);
        $this->assertSame(2000, $targetKingdom->refresh()->current_population);
    }

    public function test_direct_api_rejects_building_already_queued_manually(): void
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
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
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

        $this->actingAs($character->user);
        $response = $this->call(
            'POST',
            '/api/kingdom/capital-city/upgrade-building-requests/'.$character->id.'/'.$capitalCity->id,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ])
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'One or more buildings are already queued for upgrade.',
        ]);
        $this->assertSame(0, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $targetKingdom->refresh()->current_wood);
    }

    public function test_direct_api_rejects_building_already_queued_by_capital_city(): void
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
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        CapitalCityBuildingQueue::create([
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

        $this->actingAs($character->user);
        $response = $this->call(
            'POST',
            '/api/kingdom/capital-city/upgrade-building-requests/'.$character->id.'/'.$capitalCity->id,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ])
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'One or more buildings are already queued for upgrade.',
        ]);
        $this->assertSame(1, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
        $this->assertSame(1, $building->refresh()->level);
        $this->assertSame(2000, $targetKingdom->refresh()->current_wood);
    }

    public function test_direct_api_rejects_unit_requests_that_would_exceed_max_before_dispatch(): void
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

        UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => 5,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        CapitalCityUnitQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $gameUnit->name,
                'amount' => 3,
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $this->actingAs($character->user);
        $response = $this->call(
            'POST',
            '/api/kingdom/capital-city/recruit-unit-requests/'.$character->id.'/'.$capitalCity->id,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'request_data' => [[
                    'kingdom_id' => $targetKingdom->id,
                    'unit_requests' => [
                        [
                            'unit_name' => $gameUnit->name,
                            'unit_amount' => 2,
                        ],
                        [
                            'unit_name' => $gameUnit->name,
                            'unit_amount' => 1,
                        ],
                    ],
                ]],
            ])
        );

        $response->assertStatus(422);
        $response->assertJson([
            'message' => 'One or more unit requests exceed the maximum allowed units.',
        ]);
        Queue::assertNotPushed(CapitalCityQueueUpUnitRequests::class);
        $this->assertSame(1, CapitalCityUnitQueue::where('kingdom_id', $targetKingdom->id)->count());
    }

    public function test_capital_city_building_queue_rejects_during_automation(): void
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

        $this->actingAs($character->user);
        $response = $this->call(
            'POST',
            '/api/kingdom/capital-city/upgrade-building-requests/'.$character->id.'/'.$capitalCity->id,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ])
        );

        $response->assertStatus(422);
        Queue::assertNotPushed(CapitalCityQueueUpBuildingRequests::class);
        $this->assertSame(0, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
    }

    public function test_capital_city_unit_queue_rejects_during_automation(): void
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

        $this->actingAs($character->user);
        $response = $this->call(
            'POST',
            '/api/kingdom/capital-city/recruit-unit-requests/'.$character->id.'/'.$capitalCity->id,
            [],
            [],
            [],
            [
                'CONTENT_TYPE' => 'application/json',
                'HTTP_ACCEPT' => 'application/json',
            ],
            json_encode([
                'request_data' => [[
                    'kingdom_id' => $targetKingdom->id,
                    'unit_requests' => [[
                        'unit_name' => $gameUnit->name,
                        'unit_amount' => 1,
                    ]],
                ]],
            ])
        );

        $response->assertStatus(422);
        Queue::assertNotPushed(CapitalCityQueueUpUnitRequests::class);
        $this->assertSame(0, CapitalCityUnitQueue::where('kingdom_id', $targetKingdom->id)->count());
    }

    public function test_capital_city_building_cancel_rejects_during_automation(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
            ])
            ->assignBuilding()
            ->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();
        $building = $capitalCity->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
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

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/cancel-building-request/'.$character->id.'/'.$capitalCity->id, [
                'queue_id' => $capitalCityBuildingQueue->id,
            ]);

        $response->assertStatus(422);
        $this->assertNotNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
    }

    public function test_capital_city_unit_cancel_rejects_during_automation(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
            ])
            ->assignUnits([], 1)
            ->getKingdom();
        $characterFactory->assignAutomation([
            'type' => AutomationType::EXPLORING,
        ]);
        $character = $characterFactory->getCharacter();
        $gameUnit = $capitalCity->units()->first()->gameUnit;
        $capitalCityUnitQueue = CapitalCityUnitQueue::create([
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

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/cancel-unit-request/'.$character->id.'/'.$capitalCity->id, [
                'queue_id' => $capitalCityUnitQueue->id,
            ]);

        $response->assertStatus(422);
        $this->assertNotNull(CapitalCityUnitQueue::find($capitalCityUnitQueue->id));
    }
}
