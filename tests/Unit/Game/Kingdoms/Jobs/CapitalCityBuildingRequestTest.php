<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequest;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestMovement;
use App\Game\Kingdoms\Jobs\CapitalCityQueueUpBuildingRequests;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
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

    public function test_missing_queue_id_returns_without_exception(): void
    {
        $job = new CapitalCityBuildingRequest(999999);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $this->assertTrue(true);
    }

    public function test_repair_sets_durability_to_max_finishes_logs_and_deletes_queue(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([], [
                'current_durability' => 10,
                'max_durability' => 300,
            ]);
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

        $job = new CapitalCityBuildingRequest($capitalCityBuildingQueue->id);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertSame(300, $building->refresh()->current_durability);
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame(CapitalCityQueueStatus::FINISHED, $kingdomLog->additional_details['building_data'][0]['status']);
        $this->assertSame([
            $building->name.' has been restored to its former glory!',
        ], $kingdomLog->additional_details['messages']);
    }

    public function test_repair_morale_caps_at_one(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'current_morale' => 0.98,
            ])
            ->assignBuilding([
                'increase_morale_amount' => 0.05,
            ], [
                'current_durability' => 10,
                'max_durability' => 300,
            ]);
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

        $job = new CapitalCityBuildingRequest($capitalCityBuildingQueue->id);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $this->assertSame(1.0, $kingdom->refresh()->current_morale);
    }

    public function test_missing_building_rejects_logs_error_creates_kingdom_log_and_deletes_queue(): void
    {
        Event::fake();
        Log::spy();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();
        $buildingName = $building->name;
        $buildingId = $building->id;
        $building->delete();

        $job = new CapitalCityBuildingRequest($capitalCityBuildingQueue->id);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        Log::shouldHaveReceived('error')->once()->with(
            'Capital city building request rejected because the queued building is missing.',
            [
                'queue_id' => $capitalCityBuildingQueue->id,
                'kingdom_id' => $kingdom->id,
                'kingdom_name' => $kingdom->name,
                'building_id' => $buildingId,
                'building_name' => $buildingName,
                'request_type' => 'repair',
            ],
        );
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame($buildingName, $kingdomLog->additional_details['building_data'][0]['building_name']);
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $kingdomLog->additional_details['building_data'][0]['status']);
        $this->assertSame([
            $buildingName.' does not seem to exist in this kingdom. If this is a bug screenshot it and submit a bug report with the name of your kingdom.',
        ], $kingdomLog->additional_details['messages']);
    }

    public function test_completion_rejects_already_max_level_and_adds_message(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 1,
            ], [
                'level' => 1,
            ]);
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

        $job = new CapitalCityBuildingRequest($capitalCityBuildingQueue->id);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertSame(1, $building->refresh()->level);
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $kingdomLog->additional_details['building_data'][0]['status']);
        $this->assertSame([
            $building->name.' has been rejected: Building is already max level.',
        ], $kingdomLog->additional_details['messages']);
    }

    public function test_completion_rejects_to_level_over_max_and_adds_message(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 2,
            ], [
                'level' => 1,
            ]);
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

        $job = new CapitalCityBuildingRequest($capitalCityBuildingQueue->id);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertSame(1, $building->refresh()->level);
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $kingdomLog->additional_details['building_data'][0]['status']);
        $this->assertSame([
            $building->name.' has been rejected: Requested level is over max level.',
        ], $kingdomLog->additional_details['messages']);
    }

    public function test_stale_completion_where_current_level_differs_from_from_level_rejects_logs_error_and_adds_message(): void
    {
        Event::fake();
        Log::spy();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 2,
            ]);
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

        $job = new CapitalCityBuildingRequest($capitalCityBuildingQueue->id);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        Log::shouldHaveReceived('error')->once()->with(
            'Capital city building request rejected because the queued building level no longer matches the current building level.',
            [
                'queue_id' => $capitalCityBuildingQueue->id,
                'kingdom_id' => $kingdom->id,
                'kingdom_name' => $kingdom->name,
                'building_id' => $building->id,
                'building_name' => $building->name,
                'current_level' => 2,
                'from_level' => 1,
                'to_level' => 3,
            ],
        );
        $this->assertSame(2, $building->refresh()->level);
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $kingdomLog->additional_details['building_data'][0]['status']);
        $this->assertSame([
            'Something is wrong for '.$building->name.', the level to advance from no longer matches the current building level. Please screen shot this and report a bug and include your kingdom name.',
        ], $kingdomLog->additional_details['messages']);
    }

    public function test_upgrade_finishes_without_adding_message(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'max_level' => 5,
            ], [
                'level' => 1,
            ]);
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

        $job = new CapitalCityBuildingRequest($capitalCityBuildingQueue->id);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertSame(2, $building->refresh()->level);
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame(CapitalCityQueueStatus::FINISHED, $kingdomLog->additional_details['building_data'][0]['status']);
        $this->assertSame([], $kingdomLog->additional_details['messages']);
    }

    public function test_queue_up_building_request_dispatches_movement_on_long_running_connection(): void
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

    public function test_building_movement_redispatches_continuation_on_long_running_connection(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $queue = $kingdomManagement->getCapitalCityBuildingQueue();

        (new CapitalCityBuildingRequestMovement($queue->id))->handle(resolve(CapitalCityBuildingManagement::class));

        Queue::assertPushed(CapitalCityBuildingRequestMovement::class, function (CapitalCityBuildingRequestMovement $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }

    public function test_building_request_redispatches_continuation_on_long_running_connection(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $queue = $kingdomManagement->getCapitalCityBuildingQueue();

        (new CapitalCityBuildingRequest($queue->id))->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomMaxResourceRecalculationService::class),
        );

        Queue::assertPushed(CapitalCityBuildingRequest::class, function (CapitalCityBuildingRequest $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }
}
