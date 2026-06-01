<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\BuildingInQueue;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestCancellationMovement;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityBuildingRequestCancellationMovementTest extends TestCase
{
    use RefreshDatabase;

    public function testDelayedRedispatchPassesAllConstructorArgumentsAndUsesLongRunningQueue(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->assignCapitalCityBuildingQueue()
            ->assignCapitalCityBuildingCancellation();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();
        $capitalCityBuildingCancellation = $kingdomManagement->getCapitalCityBuildingCancellation();
        $capitalCityBuildingQueue->update([
            'status' => CapitalCityQueueStatus::BUILDING,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 2,
                'type' => 'upgrade',
            ]],
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);
        $capitalCityBuildingCancellation->update([
            'capital_city_building_queue_id' => $capitalCityBuildingQueue->id,
            'travel_time_completed_at' => now(),
        ]);

        $job = new CapitalCityBuildingRequestCancellationMovement($capitalCityBuildingCancellation->id, $capitalCityBuildingQueue->id, $character->id, [
            'building_ids' => [$building->id],
        ]);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomBuildingService::class)
        );

        Queue::assertPushed(CapitalCityBuildingRequestCancellationMovement::class, function (CapitalCityBuildingRequestCancellationMovement $queuedJob) use ($capitalCityBuildingCancellation, $capitalCityBuildingQueue, $character, $building) {
            $serializedJob = serialize($queuedJob);

            return $queuedJob->connection === 'long_running' &&
                $queuedJob->queue === 'default_long' &&
                str_contains($serializedJob, 'capitalCityCancellationQueueId";i:' . $capitalCityBuildingCancellation->id) &&
                str_contains($serializedJob, 'capitalCityQueueId";i:' . $capitalCityBuildingQueue->id) &&
                str_contains($serializedJob, 'characterId";i:' . $character->id) &&
                str_contains($serializedJob, 'building_ids') &&
                str_contains($serializedJob, 'i:' . $building->id);
        });
    }

    public function testMissingBuildingInQueueUpdatesCancellationRecordInsteadOfSourceQueue(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->assignCapitalCityBuildingQueue()
            ->assignCapitalCityBuildingCancellation();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();
        $capitalCityBuildingCancellation = $kingdomManagement->getCapitalCityBuildingCancellation();
        $capitalCityBuildingQueue->update([
            'status' => CapitalCityQueueStatus::BUILDING,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 2,
                'type' => 'upgrade',
            ]],
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);
        $capitalCityBuildingCancellation->update([
            'capital_city_building_queue_id' => $capitalCityBuildingQueue->id,
            'travel_time_completed_at' => now(),
        ]);

        $job = new CapitalCityBuildingRequestCancellationMovement($capitalCityBuildingCancellation->id, $capitalCityBuildingQueue->id, $character->id, [
            'building_ids' => [$building->id],
        ]);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomBuildingService::class)
        );

        $this->assertSame(CapitalCityQueueStatus::BUILDING, $capitalCityBuildingQueue->refresh()->status);
        $this->assertSame(CapitalCityQueueStatus::CANCELLATION_REJECTED, $capitalCityBuildingCancellation->refresh()->status);
        $this->assertSame(CapitalCityQueueStatus::CANCELLATION_REJECTED, $capitalCityBuildingQueue->refresh()->building_request_data[0]['secondary_status']);
    }

    public function testMissingSourceQueueMarksCancellationRejected(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->assignCapitalCityBuildingQueue()
            ->assignCapitalCityBuildingCancellation();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingCancellation = $kingdomManagement->getCapitalCityBuildingCancellation();
        $capitalCityBuildingCancellation->update([
            'capital_city_building_queue_id' => 999999,
            'travel_time_completed_at' => now(),
        ]);

        $job = new CapitalCityBuildingRequestCancellationMovement($capitalCityBuildingCancellation->id, 999999, $character->id, [
            'building_ids' => [$building->id],
        ]);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomBuildingService::class)
        );

        $this->assertSame(CapitalCityQueueStatus::CANCELLATION_REJECTED, $capitalCityBuildingCancellation->refresh()->status);
    }

    public function testCompletedBuildingInQueueMarksCancellationRejectedWithoutCorruptingSourceQueue(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1500,
            'current_clay' => 1500,
            'current_stone' => 1500,
            'current_iron' => 1500,
            'current_population' => 1500,
        ])
            ->assignBuilding()
            ->assignCapitalCityBuildingQueue()
            ->assignCapitalCityBuildingCancellation();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();
        $capitalCityBuildingCancellation = $kingdomManagement->getCapitalCityBuildingCancellation();
        $capitalCityBuildingQueue->update([
            'status' => CapitalCityQueueStatus::BUILDING,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 2,
                'type' => 'upgrade',
            ]],
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);
        $capitalCityBuildingCancellation->update([
            'capital_city_building_queue_id' => $capitalCityBuildingQueue->id,
            'travel_time_completed_at' => now(),
        ]);
        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'from_level' => 1,
            'to_level' => 2,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $job = new CapitalCityBuildingRequestCancellationMovement($capitalCityBuildingCancellation->id, $capitalCityBuildingQueue->id, $character->id, [
            'building_ids' => [$building->id],
        ]);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomBuildingService::class)
        );

        $this->assertSame(CapitalCityQueueStatus::BUILDING, $capitalCityBuildingQueue->refresh()->status);
        $this->assertSame(CapitalCityQueueStatus::CANCELLATION_REJECTED, $capitalCityBuildingCancellation->refresh()->status);
        $this->assertSame(CapitalCityQueueStatus::CANCELLATION_REJECTED, $capitalCityBuildingQueue->refresh()->building_request_data[0]['secondary_status']);
        $this->assertSame(1500, $kingdom->refresh()->current_wood);
    }

    public function testRetryIsIdempotentAfterSuccessfulCancellation(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->assignCapitalCityBuildingQueue()
            ->assignCapitalCityBuildingCancellation();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();
        $capitalCityBuildingCancellation = $kingdomManagement->getCapitalCityBuildingCancellation();
        $capitalCityBuildingQueue->update([
            'status' => CapitalCityQueueStatus::BUILDING,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 2,
                'type' => 'upgrade',
            ]],
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);
        $capitalCityBuildingCancellation->update([
            'capital_city_building_queue_id' => $capitalCityBuildingQueue->id,
            'travel_time_completed_at' => now(),
        ]);
        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'from_level' => 1,
            'to_level' => 2,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subMinute(),
            'completed_at' => now()->addHour(),
        ]);

        $job = new CapitalCityBuildingRequestCancellationMovement($capitalCityBuildingCancellation->id, $capitalCityBuildingQueue->id, $character->id, [
            'building_ids' => [$building->id],
        ]);
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomBuildingService::class)
        );
        $job->handle(
            resolve(CapitalCityKingdomLogHandler::class),
            resolve(KingdomBuildingService::class)
        );

        $this->assertNull(BuildingInQueue::where('building_id', $building->id)->first());
        $this->assertNull($capitalCityBuildingCancellation->fresh());
    }
}
