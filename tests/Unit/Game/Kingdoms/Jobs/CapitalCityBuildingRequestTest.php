<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestMovement;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequest;
use App\Game\Kingdoms\Jobs\CapitalCityQueueUpBuildingRequests;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Service\KingdomMaxResourceRecalculationService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityBuildingRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testMissingQueueIdReturnsWithoutException(): void
    {
        $job = new CapitalCityBuildingRequest(999999);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $this->assertTrue(true);
    }

    public function testRepairSetsDurabilityToMaxFinishesLogsAndDeletesQueue(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([], [
                'current_durability' => 10,
                'max_durability' => 300,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'repair',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $job = new CapitalCityBuildingRequest($capitalCityBuildingQueue->id);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertSame(300, $building->refresh()->current_durability);
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame(CapitalCityQueueStatus::FINISHED, $kingdomLog->additional_details['building_data'][0]['status']);
    }

    public function testRepairMoraleCapsAtOne(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_morale' => 0.98,
            ])
            ->assignBuilding([
                'increase_morale_amount' => 0.05,
            ], [
                'current_durability' => 10,
                'max_durability' => 300,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'repair',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $job = new CapitalCityBuildingRequest($capitalCityBuildingQueue->id);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $this->assertSame(1.0, $kingdom->refresh()->current_morale);
    }

    public function testMissingBuildingRejectsLogsWarningCreatesKingdomLogAndDeletesQueue(): void
    {
        Event::fake();
        Log::spy();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'repair',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);
        $buildingName = $building->name;
        $buildingId = $building->id;
        $building->delete();

        $job = new CapitalCityBuildingRequest($capitalCityBuildingQueue->id);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        Log::shouldHaveReceived('warning')->once()->with(
            'Capital city building request rejected because the queued building is missing.',
            [
                'queue_id' => $capitalCityBuildingQueue->id,
                'kingdom_id' => $kingdom->id,
                'building_id' => $buildingId,
                'request_type' => 'repair',
            ],
        );
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame($buildingName, $kingdomLog->additional_details['building_data'][0]['building_name']);
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $kingdomLog->additional_details['building_data'][0]['status']);
    }

    public function testCompletionRejectsToLevelOverMaxAndDoesNotMutateBuilding(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 1,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 2,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $job = new CapitalCityBuildingRequest($capitalCityBuildingQueue->id);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertSame(1, $building->refresh()->level);
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $kingdomLog->additional_details['building_data'][0]['status']);
    }

    public function testStaleCompletionWhereCurrentLevelDiffersFromFromLevelRejectsAndDoesNotMutateBuilding(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 2,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 3,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $job = new CapitalCityBuildingRequest($capitalCityBuildingQueue->id);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertSame(2, $building->refresh()->level);
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $kingdomLog->additional_details['building_data'][0]['status']);
    }

    public function testQueueUpBuildingRequestDispatchesMovementOnLongRunningConnection(): void
    {
        Event::fake();
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

        (new CapitalCityQueueUpBuildingRequests($character->id, $capitalCity->id, [[
            'kingdomId' => $targetKingdom->id,
            'buildingIds' => [$building->id],
        ]], 'upgrade'))->handle(resolve(CapitalCityManagementService::class));

        Queue::assertPushed(CapitalCityBuildingRequestMovement::class, function (CapitalCityBuildingRequestMovement $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }

    public function testBuildingMovementRedispatchesContinuationOnLongRunningConnection(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $queue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
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
            'completed_at' => now()->addHour(),
        ]);

        (new CapitalCityBuildingRequestMovement($queue->id))->handle(resolve(CapitalCityBuildingManagement::class));

        Queue::assertPushed(CapitalCityBuildingRequestMovement::class, function (CapitalCityBuildingRequestMovement $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }

    public function testBuildingRequestRedispatchesContinuationOnLongRunningConnection(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $queue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 2,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        (new CapitalCityBuildingRequest($queue->id))->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        Queue::assertPushed(CapitalCityBuildingRequest::class, function (CapitalCityBuildingRequest $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }
}
