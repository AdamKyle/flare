<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\CapitalCityResourceRequest as CapitalCityResourceRequestModel;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameBuilding;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomBuilding;
use App\Flare\Models\KingdomUnit;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityUnitQueueTable;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityRequestResourcesHandler;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessUnitRequestHandler;
use App\Game\Kingdoms\Jobs\CapitalCityResourceRequest;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequestMovement;
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

    public function testCapitalCityUnitResourceRejectionUpdatesUnitRequestDataAndTopLevelStatus(): void
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

    public function testCapitalCityResourceRequestReschedulesItselfWhileWaiting(): void
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

        (new CapitalCityResourceRequest(
            $capitalCityUnitQueue->id,
            $resourceRequest->id,
            CapitalCityResourceRequestType::UNIT_QUEUE
        ))->handle(
            resolve(\App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessBuildingRequestHandler::class),
            resolve(\App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessUnitRequestHandler::class)
        );

        Queue::assertPushed(CapitalCityResourceRequest::class, function (CapitalCityResourceRequest $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }

    public function testCapitalCityUnitResourceDispatchUsesLongRunningConnection(): void
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

    public function testMissingCapitalCityResourceRequestRowDoesNotLeaveQueueStuck(): void
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

        (new CapitalCityResourceRequest(
            $capitalCityUnitQueue->id,
            999,
            CapitalCityResourceRequestType::UNIT_QUEUE
        ))->handle(
            resolve(\App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessBuildingRequestHandler::class),
            resolve(\App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityProcessUnitRequestHandler::class)
        );

        $this->assertSame(CapitalCityQueueStatus::REJECTED, $capitalCityUnitQueue->refresh()->status);
    }

    public function testUnitMovementFiresTheUnitQueueTableEvent(): void
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

        (new CapitalCityUnitRequestMovement($capitalCityUnitQueue->id, $character->id))
            ->handle(resolve(\App\Game\Kingdoms\Service\CapitalCityUnitManagement::class));

        Event::assertDispatched(UpdateCapitalCityUnitQueueTable::class);
        Event::assertNotDispatched(UpdateCapitalCityBuildingQueueTable::class);
    }

    public function testUnitRequestLoggingUpdatesUnitRequestData(): void
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

        resolve(\App\Game\Kingdoms\Service\CapitalCityUnitManagement::class)->processUnitRequest($capitalCityUnitQueue);

        $this->assertSame(CapitalCityQueueStatus::CANCELLED, $capitalCityUnitQueue->refresh()->unit_request_data[0]['secondary_status']);
    }

    public function testDuplicateSameUnitRequestRowsAreNotDuplicatedWhenRecruitmentStarts(): void
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

    public function testDuplicateSameUnitRequestRowsAreAggregatedBeforeMaxValidation(): void
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
}
