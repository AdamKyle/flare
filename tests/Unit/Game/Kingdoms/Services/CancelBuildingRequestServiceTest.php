<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Game\Kingdoms\Service\CancelBuildingRequestService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CancelBuildingRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testCancelAllBuildingsNoLongerReferencesUndefinedVariable(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdomManagement = $characterFactory->kingdomManagement()->assignKingdom()->assignBuilding();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings->first();
        $character = $characterFactory->getCharacter();
        $kingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
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
            'completed_at' => now()->addHours(2),
        ]);
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

        $result = resolve(CancelBuildingRequestService::class)->handleCancelRequest($character, $kingdom, [
            'queue_id' => $capitalCityBuildingQueue->id,
        ]);

        $this->assertSame(200, $result['status']);
    }

    public function testStaleBrokenBuildingQueueCanBeSafelyCleared(): void
    {
        Event::fake();

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
                'building_name' => 'Keep',
                'secondary_status' => CapitalCityQueueStatus::PROCESSING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

        $result = resolve(CancelBuildingRequestService::class)->handleCancelRequest($character, $kingdom, [
            'queue_id' => $capitalCityBuildingQueue->id,
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
    }
}
