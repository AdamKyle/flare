<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomLog;
use App\Flare\Models\KingdomUnit;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueRequest;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitRecruitments;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessUnitRequestHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityRequestResourcesHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityRequestResourcesHandler;
use App\Game\Kingdoms\Jobs\CapitalCityResourceRequest;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequestMovement;
use App\Game\Kingdoms\Service\CapitalCityUnitManagement;
use App\Game\Kingdoms\Values\BuildingCosts;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\CapitalCityResourceRequestType;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\Kingdoms\Values\UnitNames;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityUnitManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_capital_city_unit_resource_rejection_updates_unit_request_data_and_top_level_status(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [[
                'name' => 'Spearmen',
                'amount' => 10,
                'missing_costs' => ['stone' => 100],
                'secondary_status' => CapitalCityQueueStatus::REQUESTING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $capitalCityUnitQueue = $kingdomManagement->getCapitalCityUnitQueue();

        resolve(CapitalCityRequestResourcesHandler::class)->handleResourceRequests(
            $capitalCityUnitQueue,
            $character,
            ['stone' => 100],
            $capitalCityUnitQueue->unit_request_data,
            $kingdom,
            CapitalCityResourceRequestType::UNIT_QUEUE,
        );

        $capitalCityUnitQueue = $capitalCityUnitQueue->refresh();

        $this->assertSame(CapitalCityQueueStatus::REJECTED, $capitalCityUnitQueue->status);
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $capitalCityUnitQueue->unit_request_data[0]['secondary_status']);
    }

    public function test_capital_city_resource_request_reschedules_itself_while_waiting(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $requestingKingdomManagement = $characterFactory->kingdomManagement()->assignKingdom();
        $requestingKingdom = $requestingKingdomManagement->getKingdom();
        $providingKingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $requestingKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $requestingKingdom->id,
            'requested_kingdom' => $providingKingdom->id,
            'unit_request_data' => [[
                'name' => 'Spearmen',
                'amount' => 10,
                'missing_costs' => ['stone' => 100],
                'secondary_status' => CapitalCityQueueStatus::REQUESTING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REQUESTING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $capitalCityUnitQueue = $requestingKingdomManagement->getCapitalCityUnitQueue();
        $requestingKingdomManagement->assignCapitalCityResourceRequest([
            'kingdom_requesting_id' => $requestingKingdom->id,
            'request_from_kingdom_id' => $providingKingdom->id,
            'resources' => ['stone' => 100],
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);
        $resourceRequest = $requestingKingdomManagement->getCapitalCityResourceRequest();

        $job = new CapitalCityResourceRequest(
            $capitalCityUnitQueue->id,
            $resourceRequest->id,
            CapitalCityResourceRequestType::UNIT_QUEUE,
        );

        $this->app->call([$job, 'handle']);

        Queue::assertPushed(CapitalCityResourceRequest::class, function (CapitalCityResourceRequest $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }

    public function test_capital_city_unit_resource_dispatch_uses_long_running_connection(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $characterFactory
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::RESOURCE_REQUEST_TIME_REDUCTION, 0, [
                'name' => 'Resource Request Time Reduction',
                'resource_request_time_reduction' => 0.0,
                'max_level' => 5,
            ]);
        $requestingKingdomManagement = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 0,
            'current_population' => 1000,
            'x_position' => 16,
            'y_position' => 16,
        ])->assignBuilding(['name' => BuildingCosts::MARKET_PLACE], ['level' => 5]);
        $requestingKingdom = $requestingKingdomManagement->getKingdom();
        $providingKingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1000,
            'current_population' => 1000,
            'x_position' => 32,
            'y_position' => 16,
        ])->assignBuilding(['name' => BuildingCosts::MARKET_PLACE], ['level' => 5])->assignUnits(['name' => UnitNames::SPEARMEN], 75)->getKingdom();
        $character = $characterFactory->getCharacter();
        $requestingKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $requestingKingdom->id,
            'requested_kingdom' => $providingKingdom->id,
            'unit_request_data' => [[
                'name' => UnitNames::SETTLER,
                'amount' => 1,
                'costs' => ['wood' => 100],
                'missing_costs' => ['wood' => 100],
                'secondary_status' => CapitalCityQueueStatus::REQUESTING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $capitalCityUnitQueue = $requestingKingdomManagement->getCapitalCityUnitQueue();

        resolve(CapitalCityRequestResourcesHandler::class)->handleResourceRequests(
            $capitalCityUnitQueue,
            $character,
            ['wood' => 100],
            $capitalCityUnitQueue->unit_request_data,
            $requestingKingdom,
            CapitalCityResourceRequestType::UNIT_QUEUE,
        );

        Queue::assertPushed(CapitalCityResourceRequest::class, function (CapitalCityResourceRequest $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }

    public function test_missing_capital_city_resource_request_row_does_not_leave_queue_stuck(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [[
                'name' => 'Spearmen',
                'amount' => 10,
                'missing_costs' => ['stone' => 100],
                'secondary_status' => CapitalCityQueueStatus::REQUESTING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REQUESTING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $capitalCityUnitQueue = $kingdomManagement->getCapitalCityUnitQueue();

        $job = new CapitalCityResourceRequest(
            $capitalCityUnitQueue->id,
            999,
            CapitalCityResourceRequestType::UNIT_QUEUE,
        );

        $this->app->call([$job, 'handle']);

        $this->assertSame(CapitalCityQueueStatus::REJECTED, $capitalCityUnitQueue->refresh()->status);
    }

    public function test_unit_movement_fires_the_unit_queue_table_event(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $capitalCityUnitQueue = $kingdomManagement->getCapitalCityUnitQueue();

        $job = new CapitalCityUnitRequestMovement($capitalCityUnitQueue->id, $character->id);

        $this->app->call([$job, 'handle']);

        Event::assertDispatched(UpdateCapitalCityUnitQueueTable::class);
        Event::assertNotDispatched(UpdateCapitalCityBuildingQueueTable::class);
    }

    public function test_unit_request_logging_updates_unit_request_data(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [[
                'name' => 'Spearmen',
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::CANCELLED,
                'missing_costs' => [],
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $capitalCityUnitQueue = $kingdomManagement->getCapitalCityUnitQueue();

        resolve(CapitalCityUnitManagement::class)->processUnitRequest($capitalCityUnitQueue);

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertNull($capitalCityUnitQueue->fresh());
        $this->assertSame(CapitalCityQueueStatus::CANCELLED, $kingdomLog->additional_details['unit_data'][0]['status']);
    }

    public function test_duplicate_same_unit_request_rows_are_not_duplicated_when_recruitment_starts(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1000,
            'current_clay' => 1000,
            'current_stone' => 1000,
            'current_iron' => 1000,
            'current_population' => 1000,
        ]);
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $gameUnit = GameUnit::factory()->create(['name' => 'Settlers']);
        $gameBuilding = GameBuilding::factory()->create(['name' => 'Church']);
        GameBuildingUnit::factory()->create([
            'game_building_id' => $gameBuilding->id,
            'game_unit_id' => $gameUnit->id,
            'required_level' => 1,
        ]);
        KingdomBuilding::factory()->create([
            'kingdom_id' => $kingdom->id,
            'game_building_id' => $gameBuilding->id,
            'level' => 1,
        ]);
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [
                [
                    'name' => $gameUnit->name,
                    'unit_id' => $gameUnit->id,
                    'amount' => 2,
                    'costs' => ['wood' => 20],
                    'missing_costs' => [],
                    'secondary_status' => CapitalCityQueueStatus::PROCESSING,
                ],
                [
                    'name' => $gameUnit->name,
                    'unit_id' => $gameUnit->id,
                    'amount' => 3,
                    'costs' => ['wood' => 30],
                    'missing_costs' => [],
                    'secondary_status' => CapitalCityQueueStatus::PROCESSING,
                ],
            ],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $capitalCityUnitQueue = $kingdomManagement->getCapitalCityUnitQueue();

        resolve(CapitalCityProcessUnitRequestHandler::class)->handleUnitRequests($capitalCityUnitQueue);

        $this->assertCount(2, $capitalCityUnitQueue->refresh()->unit_request_data);
    }

    public function test_duplicate_same_unit_request_rows_are_aggregated_before_max_validation(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1000,
            'current_clay' => 1000,
            'current_stone' => 1000,
            'current_iron' => 1000,
            'current_population' => 1000,
        ]);
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $gameUnit = GameUnit::factory()->create(['name' => 'Settlers']);
        $gameBuilding = GameBuilding::factory()->create(['name' => 'Church']);
        GameBuildingUnit::factory()->create([
            'game_building_id' => $gameBuilding->id,
            'game_unit_id' => $gameUnit->id,
            'required_level' => 1,
        ]);
        KingdomBuilding::factory()->create([
            'kingdom_id' => $kingdom->id,
            'game_building_id' => $gameBuilding->id,
            'level' => 1,
        ]);
        KingdomUnit::factory()->create([
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => KingdomMaxValue::MAX_UNIT - 5,
        ]);
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [
                [
                    'name' => $gameUnit->name,
                    'unit_id' => $gameUnit->id,
                    'amount' => 3,
                    'costs' => ['wood' => 30],
                    'missing_costs' => [],
                    'secondary_status' => CapitalCityQueueStatus::PROCESSING,
                ],
                [
                    'name' => $gameUnit->name,
                    'unit_id' => $gameUnit->id,
                    'amount' => 3,
                    'costs' => ['wood' => 30],
                    'missing_costs' => [],
                    'secondary_status' => CapitalCityQueueStatus::PROCESSING,
                ],
            ],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);
        $capitalCityUnitQueue = $kingdomManagement->getCapitalCityUnitQueue();

        resolve(CapitalCityProcessUnitRequestHandler::class)->handleUnitRequests($capitalCityUnitQueue);

        $requestData = $capitalCityUnitQueue->refresh()->unit_request_data;

        $this->assertSame(CapitalCityQueueStatus::RECRUITING, $requestData[0]['secondary_status']);
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $requestData[1]['secondary_status']);
    }

    public function test_mass_recruitment_across_multiple_kingdoms_creates_one_queue_per_kingdom(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdomOne = $characterFactory->kingdomManagement()->assignKingdom([
            'x_position' => 32,
            'y_position' => 16,
        ])->assignBuilding()->getKingdom();
        $targetKingdomTwo = $characterFactory->kingdomManagement()->assignKingdom([
            'x_position' => 48,
            'y_position' => 16,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $gameUnit = GameUnit::factory()->create(['name' => 'Spearmen']);
        $gameBuildingId = $targetKingdomOne->buildings()->first()->game_building_id;
        GameBuildingUnit::factory()->create([
            'game_building_id' => $gameBuildingId,
            'game_unit_id' => $gameUnit->id,
            'required_level' => 1,
        ]);
        $gameBuilding = GameBuilding::factory()->create(['name' => 'Barracks']);
        GameBuildingUnit::factory()->create(['game_building_id' => $gameBuilding->id, 'game_unit_id' => $gameUnit->id, 'required_level' => 1]);
        $characterFactory->createPassiveForCharacter(
            PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION,
            ['capital_city_unit_request_travel_time_reduction' => 0.0]
        );

        resolve(CapitalCityUnitManagement::class)->createUnitRequests($character, $capitalCity, [
            ['kingdom_id' => $targetKingdomOne->id, 'unit_requests' => [['unit_name' => $gameUnit->name, 'unit_amount' => 1]]],
            ['kingdom_id' => $targetKingdomTwo->id, 'unit_requests' => [['unit_name' => $gameUnit->name, 'unit_amount' => 1]]],
        ]);

        $this->assertCount(2, CapitalCityUnitQueue::where('status', CapitalCityQueueStatus::TRAVELING)->get());
    }

    public function test_active_capital_city_unit_queue_blocks_unit_from_being_queued(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCityManagement = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ]);
        $capitalCity = $capitalCityManagement->getKingdom();
        $targetKingdomManagement = $characterFactory->kingdomManagement()->assignKingdom([
            'x_position' => 32,
            'y_position' => 16,
        ]);
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $gameUnit = GameUnit::factory()->create(['name' => 'Spearmen']);
        $gameBuilding = GameBuilding::factory()->create(['name' => 'Barracks']);
        GameBuildingUnit::factory()->create(['game_building_id' => $gameBuilding->id, 'game_unit_id' => $gameUnit->id, 'required_level' => 1]);
        $characterFactory->createPassiveForCharacter(
            PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION,
            ['capital_city_unit_request_travel_time_reduction' => 0.0]
        );
        $targetKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $gameUnit->name,
                'amount' => 1,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        resolve(CapitalCityUnitManagement::class)->createUnitRequests($character, $capitalCity, [
            ['kingdom_id' => $targetKingdom->id, 'unit_requests' => [['unit_name' => $gameUnit->name, 'unit_amount' => 1]]],
        ]);

        $this->assertCount(0, CapitalCityUnitQueue::where('status', CapitalCityQueueStatus::TRAVELING)->get());
    }

    public function test_active_manual_unit_queue_blocks_unit_from_being_queued(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'x_position' => 32,
            'y_position' => 16,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $gameUnit = GameUnit::factory()->create(['name' => 'Spearmen']);
        $gameBuilding = GameBuilding::factory()->create(['name' => 'Barracks']);
        GameBuildingUnit::factory()->create(['game_building_id' => $gameBuilding->id, 'game_unit_id' => $gameUnit->id, 'required_level' => 1]);
        $characterFactory->createPassiveForCharacter(
            PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION,
            ['capital_city_unit_request_travel_time_reduction' => 0.0]
        );
        UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => 5,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        resolve(CapitalCityUnitManagement::class)->createUnitRequests($character, $capitalCity, [
            ['kingdom_id' => $targetKingdom->id, 'unit_requests' => [['unit_name' => $gameUnit->name, 'unit_amount' => 1]]],
        ]);

        $this->assertCount(0, CapitalCityUnitQueue::where('status', CapitalCityQueueStatus::TRAVELING)->get());
    }

    public function test_owned_units_count_against_max_in_recruitment_request(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'x_position' => 32,
            'y_position' => 16,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $gameUnit = GameUnit::factory()->create(['name' => 'Spearmen']);
        KingdomUnit::factory()->create([
            'kingdom_id' => $targetKingdom->id,
            'game_unit_id' => $gameUnit->id,
            'amount' => KingdomMaxValue::MAX_UNIT,
        ]);

        $response = resolve(CapitalCityUnitManagement::class)->createUnitRequests($character, $capitalCity, [
            [
                'kingdom_id' => $targetKingdom->id,
                'unit_requests' => [['unit_name' => $gameUnit->name, 'unit_amount' => 1]],
            ],
        ]);

        $this->assertSame(422, $response['status']);
        $this->assertSame('One or more unit requests exceed the maximum allowed units.', $response['message']);
    }

    public function test_ui_refresh_events_fire_once_for_entire_mass_request(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdomOne = $characterFactory->kingdomManagement()->assignKingdom([
            'x_position' => 32,
            'y_position' => 16,
        ])->assignBuilding()->getKingdom();
        $targetKingdomTwo = $characterFactory->kingdomManagement()->assignKingdom([
            'x_position' => 48,
            'y_position' => 16,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $gameUnit = GameUnit::factory()->create(['name' => 'Spearmen']);
        $gameBuilding = GameBuilding::factory()->create(['name' => 'Barracks']);
        GameBuildingUnit::factory()->create(['game_building_id' => $gameBuilding->id, 'game_unit_id' => $gameUnit->id, 'required_level' => 1]);
        $characterFactory->createPassiveForCharacter(
            PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION,
            ['capital_city_unit_request_travel_time_reduction' => 0.0]
        );

        resolve(CapitalCityUnitManagement::class)->createUnitRequests($character, $capitalCity, [
            ['kingdom_id' => $targetKingdomOne->id, 'unit_requests' => [['unit_name' => $gameUnit->name, 'unit_amount' => 1]]],
            ['kingdom_id' => $targetKingdomTwo->id, 'unit_requests' => [['unit_name' => $gameUnit->name, 'unit_amount' => 1]]],
        ]);

        Event::assertDispatchedTimes(UpdateCapitalCityUnitRecruitments::class, 1);
        Event::assertDispatchedTimes(UpdateCapitalCityUnitQueueTable::class, 1);
    }

    public function test_per_kingdom_progress_events_fire_for_mass_recruitment_request(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdomOne = $characterFactory->kingdomManagement()->assignKingdom([
            'x_position' => 32,
            'y_position' => 16,
        ])->assignBuilding()->getKingdom();
        $targetKingdomTwo = $characterFactory->kingdomManagement()->assignKingdom([
            'x_position' => 48,
            'y_position' => 16,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $gameUnit = GameUnit::factory()->create(['name' => 'Spearmen']);
        $gameBuildingId = $targetKingdomOne->buildings()->first()->game_building_id;
        GameBuildingUnit::factory()->create([
            'game_building_id' => $gameBuildingId,
            'game_unit_id' => $gameUnit->id,
            'required_level' => 1,
        ]);
        $characterFactory->createPassiveForCharacter(
            PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION,
            ['capital_city_unit_request_travel_time_reduction' => 0.0]
        );

        resolve(CapitalCityUnitManagement::class)->createUnitRequests($character, $capitalCity, [
            ['kingdom_id' => $targetKingdomOne->id, 'unit_requests' => [['unit_name' => $gameUnit->name, 'unit_amount' => 1]]],
            ['kingdom_id' => $targetKingdomTwo->id, 'unit_requests' => [['unit_name' => $gameUnit->name, 'unit_amount' => 1]]],
        ]);

        Event::assertDispatchedTimes(UpdateCapitalCityUnitQueueRequest::class, 2);
    }

    public function test_larger_request_shape_creates_correct_number_of_queues(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdomOne = $characterFactory->kingdomManagement()->assignKingdom([
            'x_position' => 32,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdomTwo = $characterFactory->kingdomManagement()->assignKingdom([
            'x_position' => 48,
            'y_position' => 16,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $gameUnitOne = GameUnit::factory()->create(['name' => 'Spearmen']);
        $gameBuildingOne = GameBuilding::factory()->create(['name' => 'Barracks']);
        GameBuildingUnit::factory()->create(['game_building_id' => $gameBuildingOne->id, 'game_unit_id' => $gameUnitOne->id, 'required_level' => 1]);
        $gameUnitTwo = GameUnit::factory()->create(['name' => 'Archer']);
        $gameBuildingTwo = GameBuilding::factory()->create(['name' => 'Archery Range']);
        GameBuildingUnit::factory()->create(['game_building_id' => $gameBuildingTwo->id, 'game_unit_id' => $gameUnitTwo->id, 'required_level' => 1]);
        $characterFactory->createPassiveForCharacter(
            PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION,
            ['capital_city_unit_request_travel_time_reduction' => 0.0]
        );

        resolve(CapitalCityUnitManagement::class)->createUnitRequests($character, $capitalCity, [
            [
                'kingdom_id' => $targetKingdomOne->id,
                'unit_requests' => [
                    ['unit_name' => $gameUnitOne->name, 'unit_amount' => 1],
                    ['unit_name' => $gameUnitTwo->name, 'unit_amount' => 1],
                ],
            ],
            [
                'kingdom_id' => $targetKingdomTwo->id,
                'unit_requests' => [
                    ['unit_name' => $gameUnitOne->name, 'unit_amount' => 1],
                    ['unit_name' => $gameUnitTwo->name, 'unit_amount' => 1],
                ],
            ],
        ]);

        $queues = CapitalCityUnitQueue::where('status', CapitalCityQueueStatus::TRAVELING)->get();

        $this->assertCount(2, $queues);
        $this->assertTrue($queues->every(fn (CapitalCityUnitQueue $queue) => count($queue->unit_request_data) === 2));
    }
}
