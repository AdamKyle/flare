<?php

namespace Tests\Unit\Game\Kingdoms\Jobs;

use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameBuildingUnit;
use App\Flare\Models\GameUnit;
use App\Flare\Models\KingdomUnit;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Jobs\CapitalCityQueueUpUnitRequests;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequest;
use App\Game\Kingdoms\Jobs\CapitalCityUnitRequestMovement;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use App\Game\Kingdoms\Service\CapitalCityUnitManagement;
use App\Game\Kingdoms\Service\UnitService;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\KingdomMaxValue;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
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

    public function testQueueUpUnitRequestDispatchesMovementOnLongRunningConnection(): void
    {
        Event::fake();
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $characterFactory
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION, 0, [
                'name' => 'Capital City Unit Request Travel Time Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ]);
        $capitalCity = $characterFactory->kingdomManagement()->assignKingdom([
            'is_capital' => true,
            'x_position' => 16,
            'y_position' => 16,
        ])->getKingdom();
        $targetKingdom = $characterFactory->kingdomManagement()->assignKingdom([
            'x_position' => 32,
            'y_position' => 16,
        ])->assignBuilding()->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        GameBuildingUnit::factory()->create([
            'game_building_id' => $building->game_building_id,
            'game_unit_id' => $unit->id,
            'required_level' => 1,
        ]);

        (new CapitalCityQueueUpUnitRequests($character->id, $capitalCity->id, [[
            'kingdom_id' => $targetKingdom->id,
            'unit_requests' => [[
                'unit_name' => $unit->name,
                'unit_amount' => 1,
            ]],
        ]]))->handle(resolve(CapitalCityManagementService::class));

        Queue::assertPushed(CapitalCityUnitRequestMovement::class, function (CapitalCityUnitRequestMovement $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }

    public function testUnitMovementRedispatchesContinuationOnLongRunningConnection(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        $queue = CapitalCityUnitQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 1,
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        (new CapitalCityUnitRequestMovement($queue->id, $character->id))->handle(resolve(CapitalCityUnitManagement::class));

        Queue::assertPushed(CapitalCityUnitRequestMovement::class, function (CapitalCityUnitRequestMovement $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }

    public function testUnitRequestRedispatchesContinuationOnLongRunningConnection(): void
    {
        Queue::fake();

        $characterFactory = (new CharacterFactory)->createBaseCharacter()->givePlayerLocation();
        $kingdom = $characterFactory->kingdomManagement()->assignKingdom()->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
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
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        (new CapitalCityUnitRequest($queue->id, ['wood' => 10]))->handle(
            resolve(UnitService::class),
            resolve(CapitalCityKingdomLogHandler::class),
        );

        Queue::assertPushed(CapitalCityUnitRequest::class, function (CapitalCityUnitRequest $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }
}
