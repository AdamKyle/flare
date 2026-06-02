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

    public function testBrokenStaleBuildingQueueCleanupRemovesOnlyBrokenStaleRows(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $staleQueue = CapitalCityBuildingQueue::create([
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
        $validQueue = CapitalCityBuildingQueue::create([
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

        resolve(CapitalCityQueueCleanupService::class)->clean();

        $this->assertNull(CapitalCityBuildingQueue::find($staleQueue->id));
        $this->assertNotNull(CapitalCityBuildingQueue::find($validQueue->id));
    }

    public function testBrokenStaleUnitQueueCleanupRemovesOnlyBrokenStaleRows(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $staleQueue = CapitalCityUnitQueue::factory()->create([
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
        $validQueue = CapitalCityUnitQueue::factory()->create([
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

        resolve(CapitalCityQueueCleanupService::class)->clean();

        $this->assertNull(CapitalCityUnitQueue::find($staleQueue->id));
        $this->assertNotNull(CapitalCityUnitQueue::find($validQueue->id));
    }

    public function testValidFutureQueuesAreNotRemoved(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $buildingQueue = CapitalCityBuildingQueue::create([
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
        $unitQueue = CapitalCityUnitQueue::factory()->create([
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

        resolve(CapitalCityQueueCleanupService::class)->clean();

        $this->assertNotNull(CapitalCityBuildingQueue::find($buildingQueue->id));
        $this->assertNotNull(CapitalCityUnitQueue::find($unitQueue->id));
    }

    public function testStaleCapitalCityResourceRequestCleanupRemovesOnlyCompletedRows(): void
    {
        $staleRequest = CapitalCityResourceRequest::factory()->create([
            'kingdom_requesting_id' => 1,
            'request_from_kingdom_id' => 1,
            'resources' => [],
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $futureRequest = CapitalCityResourceRequest::factory()->create([
            'kingdom_requesting_id' => 1,
            'request_from_kingdom_id' => 1,
            'resources' => [],
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

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
