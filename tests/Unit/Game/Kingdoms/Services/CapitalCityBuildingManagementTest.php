<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Values\BuildingCosts;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class CapitalCityBuildingManagementTest extends TestCase
{
    use RefreshDatabase;

    private ?CharacterFactory $character;

    private ?CapitalCityBuildingManagement $capitalCityBuildingManagement;

    public function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter([], [], true, false)
            ->givePlayerLocation();

        $this->character->updateSkill('Kingmanship', [
            'skill_type' => SkillTypeValue::EFFECTS_KINGDOM->value,
        ]);

        $this->character
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::KINGDOM_DEFENCE, 0, [
                'name' => 'Kingdom Defence',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ])
            ->assignPassiveSkill(PassiveSkillTypeValue::KINGDOM_RESOURCE_GAIN, 0, [
                'name' => 'Kingdom Resource Gain',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ])
            ->assignPassiveSkill(PassiveSkillTypeValue::KINGDOM_UNIT_COST_REDUCTION, 0, [
                'name' => 'Kingdom Unit Cost Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ])
            ->assignPassiveSkill(PassiveSkillTypeValue::KINGDOM_BUILDING_COST_REDUCTION, 3, [
                'name' => 'Building Management',
                'bonus_per_level' => 0.06,
                'max_level' => 5,
            ])
            ->assignPassiveSkill(PassiveSkillTypeValue::IRON_COST_REDUCTION, 0, [
                'name' => 'Iron Cost Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ])
            ->assignPassiveSkill(PassiveSkillTypeValue::POPULATION_COST_REDUCTION, 0, [
                'name' => 'Population Cost Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ])
            ->assignPassiveSkill(PassiveSkillTypeValue::STEEL_SMELTING_TIME_REDUCTION, 0, [
                'name' => 'Steel Smelting Time Reduction',
                'bonus_per_level' => 0.0,
                'max_level' => 5,
            ]);

        $this->capitalCityBuildingManagement = resolve(CapitalCityBuildingManagement::class);
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->capitalCityBuildingManagement = null;
    }

    public function testItConsumesDiscountedResourceCostsForOneCapitalCityBuildingUpgradeWhenTheBuildingManagementPassiveIsPartiallyTrained(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $capitalCity = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->getKingdom();

        $targetKingdomManagement = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_stone' => 10000,
                'current_clay' => 10000,
                'current_wood' => 10000,
                'current_iron' => 10000,
                'current_steel' => 0,
                'current_population' => 10000,
                'max_stone' => 10000,
                'max_clay' => 10000,
                'max_wood' => 10000,
                'max_iron' => 10000,
                'max_steel' => 0,
                'max_population' => 10000,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([
                'stone_cost' => 125,
                'clay_cost' => 50,
                'wood_cost' => 100,
                'iron_cost' => 75,
                'steel_cost' => 0,
                'required_population' => 10,
            ], [
                'level' => 27,
            ]);

        $targetKingdom = $targetKingdomManagement->getKingdom();
        $building = $targetKingdom->buildings()->first();

        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'costs' => [
                    'stone' => 2870,
                    'clay' => 1148,
                    'wood' => 2296,
                    'iron' => 1722,
                    'steel' => 0,
                    'population' => 280,
                ],
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::PROCESSING,
                'from_level' => $building->level,
                'to_level' => $building->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->capitalCityBuildingManagement->processBuildingRequest($capitalCityBuildingQueue);

        $targetKingdom = $targetKingdom->refresh();

        $this->assertSame(7130, $targetKingdom->current_stone);
        $this->assertSame(8852, $targetKingdom->current_clay);
        $this->assertSame(7704, $targetKingdom->current_wood);
        $this->assertSame(8278, $targetKingdom->current_iron);
        $this->assertSame(9720, $targetKingdom->current_population);
    }

    public function testItConsumesDiscountedResourceCostsForMultipleCapitalCityBuildingUpgradesWhenTheBuildingManagementPassiveIsPartiallyTrained(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();

        $capitalCity = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->getKingdom();

        $targetKingdomManagement = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_stone' => 10000,
                'current_clay' => 10000,
                'current_wood' => 10000,
                'current_iron' => 10000,
                'current_steel' => 0,
                'current_population' => 10000,
                'max_stone' => 10000,
                'max_clay' => 10000,
                'max_wood' => 10000,
                'max_iron' => 10000,
                'max_steel' => 0,
                'max_population' => 10000,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([
                'name' => BuildingCosts::KEEP,
                'stone_cost' => 125,
                'clay_cost' => 50,
                'wood_cost' => 100,
                'iron_cost' => 75,
                'steel_cost' => 0,
                'required_population' => 10,
            ], [
                'level' => 27,
            ])
            ->assignBuilding([
                'name' => BuildingCosts::FARM,
                'stone_cost' => 125,
                'clay_cost' => 50,
                'wood_cost' => 100,
                'iron_cost' => 75,
                'steel_cost' => 0,
                'required_population' => 10,
            ], [
                'level' => 27,
            ]);

        $targetKingdom = $targetKingdomManagement->getKingdom();
        $buildings = $targetKingdom->buildings()->orderBy('id')->get();
        $firstBuilding = $buildings->first();
        $secondBuilding = $buildings->last();

        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'requested_kingdom' => $capitalCity->id,
            'building_request_data' => [[
                'building_id' => $firstBuilding->id,
                'building_name' => $firstBuilding->name,
                'costs' => [
                    'stone' => 2870,
                    'clay' => 1148,
                    'wood' => 2296,
                    'iron' => 1722,
                    'steel' => 0,
                    'population' => 280,
                ],
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::PROCESSING,
                'from_level' => $firstBuilding->level,
                'to_level' => $firstBuilding->level + 1,
            ], [
                'building_id' => $secondBuilding->id,
                'building_name' => $secondBuilding->name,
                'costs' => [
                    'stone' => 2870,
                    'clay' => 1148,
                    'wood' => 2296,
                    'iron' => 1722,
                    'steel' => 0,
                    'population' => 280,
                ],
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::PROCESSING,
                'from_level' => $secondBuilding->level,
                'to_level' => $secondBuilding->level + 1,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->capitalCityBuildingManagement->processBuildingRequest($capitalCityBuildingQueue);

        $targetKingdom = $targetKingdom->refresh();

        $this->assertSame(4260, $targetKingdom->current_stone);
        $this->assertSame(7704, $targetKingdom->current_clay);
        $this->assertSame(5408, $targetKingdom->current_wood);
        $this->assertSame(6556, $targetKingdom->current_iron);
        $this->assertSame(9440, $targetKingdom->current_population);
    }
}