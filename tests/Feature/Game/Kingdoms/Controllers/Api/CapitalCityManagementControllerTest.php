<?php

namespace Tests\Feature\Game\Kingdoms\Controllers\Api;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\Values\AutomationType;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestMovement;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequestMovement;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Service\CapitalCityUnitManagement;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
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
        $characterFactory
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION, 0, [
                'name' => 'Capital City Building Request Travel Time Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ]);
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
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/'.$character->id.'/'.$capitalCity->id, [
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ]);

        $response->assertStatus(422);
        Queue::assertNotPushed(CapitalCityBuildingRequestMovement::class);
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
        $characterFactory
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION, 0, [
                'name' => 'Capital City Unit Request Travel Time Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ]);
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
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/'.$character->id.'/'.$capitalCity->id, [
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ]);

        $response->assertStatus(422);
        Queue::assertNotPushed(CapitalCityBuildingRequestMovement::class);
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
        $characterFactory
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION, 0, [
                'name' => 'Capital City Unit Request Travel Time Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ]);
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
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/'.$character->id.'/'.$capitalCity->id, [
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ]);

        $response->assertStatus(422);
        Queue::assertNotPushed(CapitalCityBuildingRequestMovement::class);
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
        $characterFactory
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION, 0, [
                'name' => 'Capital City Building Request Travel Time Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ]);
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
            ->call('POST', '/api/kingdom/capital-city/recruit-unit-requests/'.$character->id.'/'.$capitalCity->id, [
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

        $response->assertStatus(422);
        Queue::assertNotPushed(CapitalCityUnitRequestMovement::class);
        $this->assertSame(0, CapitalCityUnitQueue::where('kingdom_id', $targetKingdom->id)->count());
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

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/'.$character->id.'/'.$capitalCity->id, [
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ]);

        $response->assertStatus(422);
        Queue::assertNotPushed(CapitalCityBuildingRequestMovement::class);
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

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/recruit-unit-requests/'.$character->id.'/'.$capitalCity->id, [
                'request_data' => [[
                    'kingdom_id' => $targetKingdom->id,
                    'unit_requests' => [[
                        'unit_name' => $gameUnit->name,
                        'unit_amount' => 1,
                    ]],
                ]],
            ]);

        $response->assertStatus(422);
        Queue::assertNotPushed(CapitalCityUnitRequestMovement::class);
        $this->assertSame(0, CapitalCityUnitQueue::where('kingdom_id', $targetKingdom->id)->count());
    }

    public function test_capital_city_building_cancel_rejects_during_automation(): void
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
            ->call('POST', '/api/kingdom/capital-city/cancel-unit-request/'.$character->id.'/'.$capitalCity->id, [
                'queue_id' => $capitalCityUnitQueue->id,
            ]);

        $response->assertStatus(422);
        $this->assertNotNull(CapitalCityUnitQueue::find($capitalCityUnitQueue->id));
    }

    public function test_capital_city_building_request_creates_queue_immediately_and_dispatches_movement_on_long_running_connection(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $characterFactory
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION, 0, [
                'name' => 'Capital City Building Request Travel Time Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ]);
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
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/'.$character->id.'/'.$capitalCity->id, [
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ]);

        $response->assertOk();
        $this->assertSame(1, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
        Queue::assertPushed(CapitalCityBuildingRequestMovement::class, function (CapitalCityBuildingRequestMovement $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
        Event::assertDispatched(UpdateCapitalCityBuildingQueueTable::class, function (UpdateCapitalCityBuildingQueueTable $event) use ($targetKingdom) {
            return count($event->buildingQueueData) === 1 &&
                $event->buildingQueueData[0]['kingdom_id'] === $targetKingdom->id &&
                $event->buildingQueueData[0]['status'] === CapitalCityQueueStatus::TRAVELING &&
                $event->buildingQueueData[0]['time_remaining'] > 0 &&
                $event->buildingQueueData[0]['timer_duration'] > 0 &&
                $event->buildingQueueData[0]['completed_at_timestamp'] > 0;
        });
    }

    public function test_capital_city_unit_request_creates_queue_immediately_and_dispatches_movement_on_long_running_connection(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $characterFactory
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION, 0, [
                'name' => 'Capital City Unit Request Travel Time Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ]);
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
        $gameBuilding = GameBuilding::factory()->create();
        GameBuildingUnit::factory()->create([
            'game_building_id' => $gameBuilding->id,
            'game_unit_id' => $gameUnit->id,
            'required_level' => 1,
        ]);

        $response = $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/recruit-unit-requests/'.$character->id.'/'.$capitalCity->id, [
                'request_data' => [[
                    'kingdom_id' => $targetKingdom->id,
                    'unit_requests' => [[
                        'unit_name' => $gameUnit->name,
                        'unit_amount' => 1,
                    ]],
                ]],
            ]);

        $response->assertOk();
        $this->assertSame(1, CapitalCityUnitQueue::where('kingdom_id', $targetKingdom->id)->count());
        Queue::assertPushed(CapitalCityUnitRequestMovement::class, function (CapitalCityUnitRequestMovement $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
        Event::assertDispatched(UpdateCapitalCityUnitQueueTable::class, function (UpdateCapitalCityUnitQueueTable $event) use ($targetKingdom) {
            return count($event->unitQueueData) === 1 &&
                $event->unitQueueData[0]['kingdom_id'] === $targetKingdom->id &&
                $event->unitQueueData[0]['status'] === CapitalCityQueueStatus::TRAVELING &&
                $event->unitQueueData[0]['time_remaining'] > 0 &&
                $event->unitQueueData[0]['timer_duration'] > 0 &&
                $event->unitQueueData[0]['completed_at_timestamp'] > 0;
        });
    }

    public function test_capital_city_unit_movement_job_does_not_create_duplicate_queue_rows(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $characterFactory
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION, 0, [
                'name' => 'Capital City Unit Request Travel Time Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ]);
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
        $gameBuilding = GameBuilding::factory()->create();
        GameBuildingUnit::factory()->create([
            'game_building_id' => $gameBuilding->id,
            'game_unit_id' => $gameUnit->id,
            'required_level' => 1,
        ]);

        $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/recruit-unit-requests/'.$character->id.'/'.$capitalCity->id, [
                'request_data' => [[
                    'kingdom_id' => $targetKingdom->id,
                    'unit_requests' => [[
                        'unit_name' => $gameUnit->name,
                        'unit_amount' => 1,
                    ]],
                ]],
            ])
            ->assertOk();

        $queue = CapitalCityUnitQueue::where('kingdom_id', $targetKingdom->id)->first();
        $queue->update([
            'completed_at' => now()->addHour(),
        ]);

        (new CapitalCityUnitRequestMovement($queue->id, $character->id))
            ->handle(resolve(CapitalCityUnitManagement::class));

        $this->assertSame(1, CapitalCityUnitQueue::where('kingdom_id', $targetKingdom->id)->count());
    }

    public function test_capital_city_building_movement_job_does_not_create_duplicate_queue_rows(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $characterFactory
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION, 0, [
                'name' => 'Capital City Building Request Travel Time Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ]);
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

        $this->actingAs($character->user)
            ->call('POST', '/api/kingdom/capital-city/upgrade-building-requests/'.$character->id.'/'.$capitalCity->id, [
                'request_type' => 'upgrade',
                'request_data' => [[
                    'kingdomId' => $targetKingdom->id,
                    'buildingIds' => [$building->id],
                ]],
            ])
            ->assertOk();

        $queue = CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->first();
        $queue->update([
            'completed_at' => now()->addHour(),
        ]);

        (new CapitalCityBuildingRequestMovement($queue->id))
            ->handle(resolve(CapitalCityBuildingManagement::class));

        $this->assertSame(1, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
    }
}
