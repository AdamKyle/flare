<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomUnit;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequest;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityUnitRequestTest extends TestCase
{
    use RefreshDatabase;

    public function testOverMaximumCompletionIsRejectedWithoutSpendingResources(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 100,
            'current_clay' => 100,
            'current_stone' => 100,
            'current_iron' => 100,
            'current_population' => 100,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create();
        KingdomUnit::factory()->create([
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => KingdomMaxValue::MAX_UNIT,
        ]);
        $queue = CapitalCityUnitQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 1,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        (new CapitalCityUnitRequest($queue->id, ['wood' => 10]))->handle(
            resolve(UnitService::class),
            resolve(CapitalCityKingdomLogHandler::class),
        );

        $this->assertNull(CapitalCityUnitQueue::find($queue->id));
        $this->assertSame(100, $kingdom->refresh()->current_wood);
    }

    public function testRejectedRowsAreNotChargedWhenAcceptedRowsComplete(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 100,
            'current_clay' => 100,
            'current_stone' => 100,
            'current_iron' => 100,
            'current_population' => 100,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        $queue = CapitalCityUnitQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [
                [
                    'name' => $unit->name,
                    'amount' => 1,
                    'secondary_status' => CapitalCityQueueStatus::RECRUITING,
                    'costs' => ['wood' => 10],
                ],
                [
                    'name' => $unit->name,
                    'amount' => 1,
                    'secondary_status' => CapitalCityQueueStatus::REJECTED,
                    'costs' => ['wood' => 10],
                ],
            ],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        (new CapitalCityUnitRequest($queue->id, ['wood' => 20]))->handle(
            resolve(UnitService::class),
            resolve(CapitalCityKingdomLogHandler::class),
        );

        $this->assertSame(90, $kingdom->refresh()->current_wood);
    }

    public function testDuplicateAcceptedRowsCannotCompleteOverMaximumUnits(): void
    {
        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'current_wood' => 100,
            'current_clay' => 100,
            'current_stone' => 100,
            'current_iron' => 100,
            'current_population' => 100,
        ])->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create(['name' => 'Settlers']);
        KingdomUnit::factory()->create([
            'kingdom_id' => $kingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => KingdomMaxValue::MAX_UNIT - 5,
        ]);
        $queue = CapitalCityUnitQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [
                [
                    'name' => $unit->name,
                    'amount' => 3,
                    'secondary_status' => CapitalCityQueueStatus::RECRUITING,
                    'costs' => ['wood' => 10],
                ],
                [
                    'name' => $unit->name,
                    'amount' => 3,
                    'secondary_status' => CapitalCityQueueStatus::RECRUITING,
                    'costs' => ['wood' => 10],
                ],
            ],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);

        (new CapitalCityUnitRequest($queue->id, ['wood' => 20]))->handle(
            resolve(UnitService::class),
            resolve(CapitalCityKingdomLogHandler::class),
        );

        $this->assertSame(KingdomMaxValue::MAX_UNIT - 2, $kingdom->units()->where('game_unit_id', $unit->id)->first()->amount);
        $this->assertSame(90, $kingdom->refresh()->current_wood);
    }
}
