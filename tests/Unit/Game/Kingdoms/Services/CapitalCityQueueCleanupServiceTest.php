<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityResourceRequest;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Service\CapitalCityQueueCleanupService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityQueueCleanupServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_broken_stale_building_queue_cleanup_removes_only_broken_stale_rows(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $kingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => 999,
                'secondary_status' => CapitalCityQueueStatus::PROCESSING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $staleQueue = $kingdomManagement->getCapitalCityBuildingQueue();
        $kingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => 999,
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $validQueue = $kingdomManagement->getCapitalCityBuildingQueue();

        resolve(CapitalCityQueueCleanupService::class)->clean();

        $this->assertNull(CapitalCityBuildingQueue::find($staleQueue->id));
        $this->assertNotNull(CapitalCityBuildingQueue::find($validQueue->id));
    }

    public function test_broken_stale_unit_queue_cleanup_removes_only_broken_stale_rows(): void
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
                'secondary_status' => CapitalCityQueueStatus::REQUESTING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REQUESTING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $staleQueue = $kingdomManagement->getCapitalCityUnitQueue();
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [[
                'name' => 'Spearmen',
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $validQueue = $kingdomManagement->getCapitalCityUnitQueue();

        resolve(CapitalCityQueueCleanupService::class)->clean();

        $this->assertNull(CapitalCityUnitQueue::find($staleQueue->id));
        $this->assertNotNull(CapitalCityUnitQueue::find($validQueue->id));
    }

    public function test_valid_future_queues_are_not_removed(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding();
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $kingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => 999,
                'secondary_status' => CapitalCityQueueStatus::PROCESSING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $buildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();
        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [[
                'name' => 'Spearmen',
                'secondary_status' => CapitalCityQueueStatus::REQUESTING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REQUESTING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $unitQueue = $kingdomManagement->getCapitalCityUnitQueue();

        resolve(CapitalCityQueueCleanupService::class)->clean();

        $this->assertNotNull(CapitalCityBuildingQueue::find($buildingQueue->id));
        $this->assertNotNull(CapitalCityUnitQueue::find($unitQueue->id));
    }

    public function testStaleCapitalCityResourceRequestCleanupRemovesOnlyCompletedRows(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom();
        $kingdomManagement->assignCapitalCityResourceRequest([
            'kingdom_requesting_id' => 1,
            'request_from_kingdom_id' => 1,
            'resources' => [],
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $staleRequest = $kingdomManagement->getCapitalCityResourceRequest();
        $kingdomManagement->assignCapitalCityResourceRequest([
            'kingdom_requesting_id' => 1,
            'request_from_kingdom_id' => 1,
            'resources' => [],
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $futureRequest = $kingdomManagement->getCapitalCityResourceRequest();

        Log::shouldReceive('warning')
            ->once()
            ->with('Deleted stale capital city resource request.', [
                'resource_request_id' => $staleRequest->id,
            ]);

        (new CapitalCityQueueCleanupService)->clean();

        $this->assertNull(CapitalCityResourceRequest::find($staleRequest->id));
        $this->assertNotNull(CapitalCityResourceRequest::find($futureRequest->id));
    }
}
