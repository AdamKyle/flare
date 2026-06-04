<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\CapitalCityUnitQueue;
use App\Flare\Models\GameUnit;
use App\Flare\Models\UnitInQueue;
use App\Game\Kingdoms\Service\CapitalCityManagementService;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityManagementServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_fetch_buildings_for_repairs_includes_damaged_non_queued_buildings(): void
    {
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
            ->assignBuilding([], [
                'current_durability' => 99,
                'max_durability' => 100,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();

        $result = resolve(CapitalCityManagementService::class)
            ->fetchBuildingsForUpgradesOrRepairs($character, $capitalCity, true);

        $this->assertSame($targetKingdom->id, $result[0]['kingdom_id']);
        $this->assertSame($targetKingdom->buildings()->first()->id, $result[0]['buildings'][0]['id']);
        $this->assertTrue($result[0]['buildings'][0]['can_be_repaired']);
        $this->assertFalse($result[0]['buildings'][0]['can_be_upgraded']);
    }

    public function test_fetch_buildings_for_repairs_excludes_manually_queued_buildings(): void
    {
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
            ->assignBuilding([], [
                'current_durability' => 1,
                'max_durability' => 100,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'building_id' => $building->id,
            'to_level' => $building->level,
            'type' => BuildingQueueType::REPAIR,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $result = resolve(CapitalCityManagementService::class)
            ->fetchBuildingsForUpgradesOrRepairs($character, $capitalCity, true);

        $this->assertSame($targetKingdom->id, $result[0]['kingdom_id']);
        $this->assertSame([], $result[0]['buildings']);
    }

    public function test_fetch_buildings_for_repairs_excludes_capital_city_queued_buildings(): void
    {
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
        $targetKingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([], [
                'current_durability' => 1,
                'max_durability' => 100,
            ]);
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'repair',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::REPAIRING,
                'from_level' => null,
                'to_level' => null,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REPAIRING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $result = resolve(CapitalCityManagementService::class)
            ->fetchBuildingsForUpgradesOrRepairs($character, $capitalCity, true);

        $this->assertSame([], $result);
    }

    public function test_fetch_building_queue_data_keeps_ready_active_building_queues(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
            ])
            ->getKingdom();
        $targetKingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $travelingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();
        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $buildingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();
        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'repair',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::REPAIRING,
                'from_level' => $building->level,
                'to_level' => $building->level,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REPAIRING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $repairingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();

        $result = resolve(CapitalCityManagementService::class)->fetchBuildingQueueData($character, $capitalCity);

        $this->assertCount(3, $result);
        $this->assertNotNull(CapitalCityBuildingQueue::find($travelingQueue->id));
        $this->assertNotNull(CapitalCityBuildingQueue::find($buildingQueue->id));
        $this->assertNotNull(CapitalCityBuildingQueue::find($repairingQueue->id));
    }

    public function test_fetch_unit_queue_data_keeps_ready_active_unit_queues(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
            ])
            ->getKingdom();
        $targetKingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom();
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();

        $targetKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => 'Spearmen',
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $travelingQueue = $targetKingdomManagement->getCapitalCityUnitQueue();
        $targetKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => 'Archers',
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);
        $recruitingQueue = $targetKingdomManagement->getCapitalCityUnitQueue();

        $result = resolve(CapitalCityManagementService::class)->fetchUnitQueueData($character, $capitalCity);

        $this->assertCount(2, $result);
        $this->assertNotNull(CapitalCityUnitQueue::find($travelingQueue->id));
        $this->assertNotNull(CapitalCityUnitQueue::find($recruitingQueue->id));
    }

    public function test_fetch_kingdoms_for_selection_keeps_kingdom_with_other_available_units_when_capital_city_unit_is_queued(): void
    {
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
            ]);
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        $availableUnit = GameUnit::factory()->create(['name' => 'Archers']);

        $targetKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => $unit->name,
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::RECRUITING,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $result = resolve(CapitalCityManagementService::class)->fetchKingdomsForSelection($capitalCity, true);

        $this->assertSame($targetKingdom->id, $result[0]['id']);
        $this->assertContains($availableUnit->name, $result[0]['available_unit_types']);
        $this->assertNotContains($unit->name, $result[0]['available_unit_types']);
    }

    public function test_fetch_kingdoms_for_selection_excludes_active_manually_queued_unit_type(): void
    {
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
        $targetKingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);
        $availableUnit = GameUnit::factory()->create(['name' => 'Archers']);

        UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 10,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $result = resolve(CapitalCityManagementService::class)->fetchKingdomsForSelection($capitalCity, true);

        $this->assertSame($targetKingdom->id, $result[0]['id']);
        $this->assertContains($availableUnit->name, $result[0]['available_unit_types']);
        $this->assertNotContains($unit->name, $result[0]['available_unit_types']);
    }

    public function test_fetch_kingdoms_for_selection_keeps_expired_manual_queued_unit_type(): void
    {
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
        $targetKingdom = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->getKingdom();
        $character = $characterFactory->getCharacter();
        $unit = GameUnit::factory()->create(['name' => 'Spearmen']);

        UnitInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'game_unit_id' => $unit->id,
            'amount' => 10,
            'started_at' => now()->subHours(2),
            'completed_at' => now()->subHour(),
        ]);

        $result = resolve(CapitalCityManagementService::class)->fetchKingdomsForSelection($capitalCity, true);

        $this->assertSame($targetKingdom->id, $result[0]['id']);
        $this->assertContains($unit->name, $result[0]['available_unit_types']);
    }

    public function test_fetch_building_queue_data_includes_phase_timer_labels(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
            ])
            ->getKingdom();
        $travelingKingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $travelingKingdom = $travelingKingdomManagement->getKingdom();
        $buildingKingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $buildingKingdom = $buildingKingdomManagement->getKingdom();
        $repairingKingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $repairingKingdom = $repairingKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $travelingBuilding = $travelingKingdom->buildings()->first();
        $building = $buildingKingdom->buildings()->first();
        $repairingBuilding = $repairingKingdom->buildings()->first();

        $travelingKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $travelingKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $travelingBuilding->id,
                'building_name' => $travelingBuilding->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::TRAVELING,
                'from_level' => $travelingBuilding->level,
                'to_level' => $travelingBuilding->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $travelingQueue = $travelingKingdomManagement->getCapitalCityBuildingQueue();
        $buildingKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $buildingKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::BUILDING,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $buildingQueue = $buildingKingdomManagement->getCapitalCityBuildingQueue();
        $repairingKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $repairingKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $repairingBuilding->id,
                'building_name' => $repairingBuilding->name,
                'type' => 'repair',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::REPAIRING,
                'from_level' => $repairingBuilding->level,
                'to_level' => $repairingBuilding->level,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REPAIRING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);
        $repairingQueue = $repairingKingdomManagement->getCapitalCityBuildingQueue();

        $result = resolve(CapitalCityManagementService::class)->fetchBuildingQueueData($character, $capitalCity);

        $phaseTimerLabelsByQueueId = collect($result)->pluck('phase_timer_label', 'queue_id')->toArray();

        $this->assertSame('Traveling', $phaseTimerLabelsByQueueId[$travelingQueue->id]);
        $this->assertSame('Building', $phaseTimerLabelsByQueueId[$buildingQueue->id]);
        $this->assertSame('Repairing', $phaseTimerLabelsByQueueId[$repairingQueue->id]);
    }

    public function test_fetch_building_queue_data_includes_cancellation_rejected_requests(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
            ])
            ->getKingdom();
        $targetKingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();
        $building = $targetKingdom->buildings()->first();

        $targetKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::CANCELLATION_REJECTED,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::BUILDING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $result = resolve(CapitalCityManagementService::class)->fetchBuildingQueueData($character, $capitalCity);

        $this->assertSame(CapitalCityQueueStatus::CANCELLATION_REJECTED, $result[0]['building_queue'][0]['secondary_status']);
    }

    public function test_fetch_unit_queue_data_includes_cancellation_rejected_requests(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter()
            ->givePlayerLocation();
        $capitalCity = $characterFactory
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
            ])
            ->getKingdom();
        $targetKingdomManagement = $characterFactory
            ->kingdomManagement()
            ->assignKingdom();
        $targetKingdom = $targetKingdomManagement->getKingdom();
        $character = $characterFactory->getCharacter();

        $targetKingdomManagement->assignCapitalCityUnitQueue([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'unit_request_data' => [[
                'name' => 'Spearmen',
                'amount' => 10,
                'secondary_status' => CapitalCityQueueStatus::CANCELLATION_REJECTED,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::RECRUITING,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $result = resolve(CapitalCityManagementService::class)->fetchUnitQueueData($character, $capitalCity);

        $this->assertSame(CapitalCityQueueStatus::CANCELLATION_REJECTED, $result[0]['unit_requests'][0]['secondary_status']);
    }
}
