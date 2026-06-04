<?php

namespace Tests\Unit\Game\Kingdoms\Handlers;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityKingdomLogHandler;
use App\Game\Kingdoms\Jobs\CapitalCityQueueUpBuildingRequests;
use App\Game\Kingdoms\Jobs\CapitalCityQueueUpUnitRequests;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use App\Game\Kingdoms\Values\BuildingCosts;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\UnitNames;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;
use Tests\Traits\CreateGameBuildingUnit;

class CapitalCityQueueMessagesTest extends TestCase
{
    use CreateGameBuildingUnit, RefreshDatabase;

    public function test_building_queue_log_uses_empty_messages_when_queue_messages_are_null(): void
    {
        Event::fake();

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
                'secondary_status' => CapitalCityQueueStatus::FINISHED,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => null,
            'status' => CapitalCityQueueStatus::FINISHED,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

        resolve(CapitalCityKingdomLogHandler::class)->possiblyCreateLogForBuildingQueue($capitalCityBuildingQueue);

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertSame([], $kingdomLog->additional_details['messages']);
    }

    public function test_unit_queue_log_uses_empty_messages_when_queue_messages_are_null(): void
    {
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $kingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignUnits(['name' => UnitNames::SPEARMEN]);
        $kingdom = $kingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $kingdomUnit = $kingdom->units()->with('gameUnit')->first();

        $kingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'unit_request_data' => [[
                'name' => $kingdomUnit->gameUnit->name,
                'amount' => 1,
                'secondary_status' => CapitalCityQueueStatus::FINISHED,
            ]],
            'messages' => null,
            'status' => CapitalCityQueueStatus::FINISHED,
            'started_at' => now()->subMinute(),
            'completed_at' => now(),
        ]);
        $capitalCityUnitQueue = $kingdomManagement->getCapitalCityUnitQueue();

        resolve(CapitalCityKingdomLogHandler::class)->possiblyCreateLogForUnitQueue($capitalCityUnitQueue);

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertSame([], $kingdomLog->additional_details['messages']);
    }

    public function test_building_request_queue_starts_with_empty_messages(): void
    {
        Queue::fake();
        Event::fake();

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
                'name' => BuildingCosts::KEEP,
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

        $capitalCityBuildingQueue = CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->first();

        $this->assertSame([], $capitalCityBuildingQueue->messages);
    }

    public function test_unit_request_queue_starts_with_empty_messages(): void
    {
        Queue::fake();
        Event::fake();

        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();

        $characterFactory
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_UNIT_TRAVEL_TIME_REDUCTION, 0, [
                'name' => 'Capital City Unit Request Travel Time Reduction',
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

        $targetKingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([
                'name' => BuildingCosts::BARRACKS,
                'trains_units' => true,
            ], [
                'level' => 1,
            ])
            ->assignUnits(['name' => UnitNames::SPEARMEN]);

        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();
        $kingdomUnit = $targetKingdom->units()->first();

        $this->createGameBuildingUnit([
            'game_building_id' => $building->game_building_id,
            'game_unit_id' => $kingdomUnit->game_unit_id,
            'required_level' => 1,
        ]);

        (new CapitalCityQueueUpUnitRequests($character->id, $capitalCity->id, [[
            'kingdom_id' => $targetKingdom->id,
            'unit_requests' => [[
                'unit_name' => UnitNames::SPEARMEN,
                'unit_amount' => 1,
            ]],
        ]]))->handle(resolve(CapitalCityManagementService::class));

        $capitalCityUnitQueue = CapitalCityUnitQueue::where('kingdom_id', $targetKingdom->id)->first();

        $this->assertSame([], $capitalCityUnitQueue->messages);
    }
}
