<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingCancellation;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequestCancellationMovement;
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

    public function test_delayed_redispatch_passes_all_constructor_arguments(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding()->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 2,
                'type' => 'upgrade',
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);
        $capitalCityBuildingCancellation = CapitalCityBuildingCancellation::create([
            'building_id' => $building->id,
            'kingdom_id' => $kingdom->id,
            'request_kingdom_id' => $kingdom->id,
            'character_id' => $character->id,
            'capital_city_building_queue_id' => $capitalCityBuildingQueue->id,
            'status' => CapitalCityQueueStatus::TRAVELING,
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

            return str_contains($serializedJob, 'capitalCityCancellationQueueId";i:'.$capitalCityBuildingCancellation->id) &&
                str_contains($serializedJob, 'capitalCityQueueId";i:'.$capitalCityBuildingQueue->id) &&
                str_contains($serializedJob, 'characterId";i:'.$character->id) &&
                str_contains($serializedJob, 'building_ids') &&
                str_contains($serializedJob, 'i:'.$building->id);
        });
    }

    public function test_missing_building_in_queue_updates_cancellation_record_instead_of_source_queue(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding()->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 2,
                'type' => 'upgrade',
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);
        $capitalCityBuildingCancellation = CapitalCityBuildingCancellation::create([
            'building_id' => $building->id,
            'kingdom_id' => $kingdom->id,
            'request_kingdom_id' => $kingdom->id,
            'character_id' => $character->id,
            'capital_city_building_queue_id' => $capitalCityBuildingQueue->id,
            'status' => CapitalCityQueueStatus::TRAVELING,
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

    public function test_missing_source_queue_marks_cancellation_rejected(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding()->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingCancellation = CapitalCityBuildingCancellation::create([
            'building_id' => $building->id,
            'kingdom_id' => $kingdom->id,
            'request_kingdom_id' => $kingdom->id,
            'character_id' => $character->id,
            'capital_city_building_queue_id' => 999999,
            'status' => CapitalCityQueueStatus::TRAVELING,
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

    public function test_completed_building_in_queue_marks_cancellation_rejected_without_corrupting_source_queue(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 1500,
            'current_clay' => 1500,
            'current_stone' => 1500,
            'current_iron' => 1500,
            'current_population' => 1500,
        ])->assignBuilding()->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 2,
                'type' => 'upgrade',
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);
        $capitalCityBuildingCancellation = CapitalCityBuildingCancellation::create([
            'building_id' => $building->id,
            'kingdom_id' => $kingdom->id,
            'request_kingdom_id' => $kingdom->id,
            'character_id' => $character->id,
            'capital_city_building_queue_id' => $capitalCityBuildingQueue->id,
            'status' => CapitalCityQueueStatus::TRAVELING,
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

    public function test_retry_is_idempotent_after_successful_cancellation(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding()->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => 1,
                'to_level' => 2,
                'type' => 'upgrade',
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);
        $capitalCityBuildingCancellation = CapitalCityBuildingCancellation::create([
            'building_id' => $building->id,
            'kingdom_id' => $kingdom->id,
            'request_kingdom_id' => $kingdom->id,
            'character_id' => $character->id,
            'capital_city_building_queue_id' => $capitalCityBuildingQueue->id,
            'status' => CapitalCityQueueStatus::TRAVELING,
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
        $this->assertNull(CapitalCityBuildingCancellation::find($capitalCityBuildingCancellation->id));
    }
}
