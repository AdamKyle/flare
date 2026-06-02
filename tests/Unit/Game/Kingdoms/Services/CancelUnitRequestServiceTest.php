<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Game\Kingdoms\Service\CancelUnitRequestService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CancelUnitRequestServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testStaleBrokenUnitQueueCanBeSafelyCleared(): void
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
                'secondary_status' => CapitalCityQueueStatus::REQUESTING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REQUESTING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $capitalCityUnitQueue = $kingdomManagement->getCapitalCityUnitQueue();

        $result = resolve(CancelUnitRequestService::class)->handleCancelRequest($character, $kingdom, [
            'queue_id' => $capitalCityUnitQueue->id,
        ]);

        $this->assertSame(200, $result['status']);
        $this->assertNull(CapitalCityUnitQueue::find($capitalCityUnitQueue->id));
    }
}
