<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Jobs\CapitalCityBuildingRequest;
use App\Game\Kingdoms\Service\KingdomMaxResourceRecalculationService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityBuildingRequestTest extends TestCase
{
    use RefreshDatabase;

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
}
