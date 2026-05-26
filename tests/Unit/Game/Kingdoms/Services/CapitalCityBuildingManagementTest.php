<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityRequestResourcesHandler;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Values\BuildingCosts;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\CapitalCityResourceRequestType;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use App\Game\Skills\Values\SkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
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
            ])
            ->assignPassiveSkill(PassiveSkillTypeValue::CAPITAL_CITY_REQUEST_BUILD_TRAVEL_TIME_REDUCTION, 0, [
                'name' => 'Capital City Building Request Travel Time Reduction',
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

    public function testCapitalCityBuildingResourceRejectionUpdatesBuildingRequestDataAndTopLevelStatus(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $kingdom = $this->character
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding()
            ->getKingdom();
        $building = $kingdom->buildings->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => 'Keep',
                'missing_costs' => ['stone' => 100],
                'secondary_status' => CapitalCityQueueStatus::REQUESTING,
                'from_level' => 1,
                'to_level' => 2,
                'type' => 'upgrade',
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        resolve(CapitalCityRequestResourcesHandler::class)->handleResourceRequests(
            $capitalCityBuildingQueue,
            $character,
            ['stone' => 100],
            $capitalCityBuildingQueue->building_request_data,
            $kingdom,
            CapitalCityResourceRequestType::BUILDING_QUEUE,
        );

        $capitalCityBuildingQueue = $capitalCityBuildingQueue->refresh();

        $this->assertSame(CapitalCityQueueStatus::REJECTED, $capitalCityBuildingQueue->status);
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $capitalCityBuildingQueue->building_request_data[0]['secondary_status']);
    }

    public function testMixedValidAndMaxLevelBuildingRequestSkipsMaxLevelWithoutSpendingForIt(): void
    {
        Event::fake();
        Queue::fake();

        $character = $this->character->getCharacter();
        $capitalCity = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'is_capital' => true,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->getKingdom();
        $targetKingdom = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 5000,
                'current_clay' => 5000,
                'current_stone' => 5000,
                'current_iron' => 5000,
                'current_population' => 5000,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding([
                'name' => BuildingCosts::KEEP,
                'max_level' => 5,
                'stone_cost' => 100,
                'clay_cost' => 100,
                'wood_cost' => 100,
                'iron_cost' => 100,
                'steel_cost' => 0,
                'required_population' => 10,
            ], [
                'level' => 1,
            ])
            ->assignBuilding([
                'name' => BuildingCosts::FARM,
                'max_level' => 1,
                'stone_cost' => 1000,
                'clay_cost' => 1000,
                'wood_cost' => 1000,
                'iron_cost' => 1000,
                'steel_cost' => 0,
                'required_population' => 100,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $validBuilding = $targetKingdom->buildings()->orderBy('id')->first();
        $maxLevelBuilding = $targetKingdom->buildings()->orderBy('id')->get()->last();

        $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $capitalCity, [[
            'kingdomId' => $targetKingdom->id,
            'buildingIds' => [$validBuilding->id, $maxLevelBuilding->id],
        ]], 'upgrade');

        $capitalCityBuildingQueue = CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->first();

        $this->assertNotNull($capitalCityBuildingQueue);
        $this->assertCount(1, $capitalCityBuildingQueue->building_request_data);
        $this->assertSame($validBuilding->id, $capitalCityBuildingQueue->building_request_data[0]['building_id']);
        $this->assertSame(5000, $targetKingdom->refresh()->current_wood);
        $this->assertSame(5000, $targetKingdom->refresh()->current_clay);
        $this->assertSame(5000, $targetKingdom->refresh()->current_stone);
        $this->assertSame(5000, $targetKingdom->refresh()->current_iron);
        $this->assertSame(5000, $targetKingdom->refresh()->current_population);
    }

    public function testNoResourcesAreSpentForRejectedMaxLevelBuildingsDuringProcessing(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $kingdom = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 5000,
                'current_clay' => 5000,
                'current_stone' => 5000,
                'current_iron' => 5000,
                'current_population' => 5000,
            ])
            ->assignBuilding([
                'max_level' => 1,
                'stone_cost' => 1000,
                'clay_cost' => 1000,
                'wood_cost' => 1000,
                'iron_cost' => 1000,
                'steel_cost' => 0,
                'required_population' => 100,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'costs' => [
                    'stone' => 1000,
                    'clay' => 1000,
                    'wood' => 1000,
                    'iron' => 1000,
                    'steel' => 0,
                    'population' => 100,
                ],
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::PROCESSING,
                'from_level' => 1,
                'to_level' => 2,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->capitalCityBuildingManagement->processBuildingRequest($capitalCityBuildingQueue);

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $kingdomLog->additional_details['building_data'][0]['status']);
        $this->assertSame(5000, $kingdom->refresh()->current_wood);
        $this->assertSame(5000, $kingdom->refresh()->current_clay);
        $this->assertSame(5000, $kingdom->refresh()->current_stone);
        $this->assertSame(5000, $kingdom->refresh()->current_iron);
        $this->assertSame(5000, $kingdom->refresh()->current_population);
    }

    public function testStaleProcessingPathRejectsAndDoesNotSpendResources(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $kingdom = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 5000,
                'current_clay' => 5000,
                'current_stone' => 5000,
                'current_iron' => 5000,
                'current_population' => 5000,
            ])
            ->assignBuilding([
                'max_level' => 5,
                'stone_cost' => 1000,
                'clay_cost' => 1000,
                'wood_cost' => 1000,
                'iron_cost' => 1000,
                'steel_cost' => 0,
                'required_population' => 100,
            ], [
                'level' => 2,
            ])
            ->getKingdom();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'costs' => [
                    'stone' => 1000,
                    'clay' => 1000,
                    'wood' => 1000,
                    'iron' => 1000,
                    'steel' => 0,
                    'population' => 100,
                ],
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::PROCESSING,
                'from_level' => 1,
                'to_level' => 3,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->capitalCityBuildingManagement->processBuildingRequest($capitalCityBuildingQueue);

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertSame(2, $building->refresh()->level);
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $kingdomLog->additional_details['building_data'][0]['status']);
        $this->assertSame(5000, $kingdom->refresh()->current_wood);
        $this->assertSame(5000, $kingdom->refresh()->current_clay);
        $this->assertSame(5000, $kingdom->refresh()->current_stone);
        $this->assertSame(5000, $kingdom->refresh()->current_iron);
        $this->assertSame(5000, $kingdom->refresh()->current_population);
    }

    public function testOverMaxQueueDataRejectsDuringProcessingWithoutSpendingOrMutating(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $kingdom = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 5000,
                'current_clay' => 5000,
                'current_stone' => 5000,
                'current_iron' => 5000,
                'current_population' => 5000,
            ])
            ->assignBuilding([
                'max_level' => 2,
                'stone_cost' => 1000,
                'clay_cost' => 1000,
                'wood_cost' => 1000,
                'iron_cost' => 1000,
                'steel_cost' => 0,
                'required_population' => 100,
            ], [
                'level' => 1,
            ])
            ->getKingdom();
        $building = $kingdom->buildings()->first();
        $capitalCityBuildingQueue = CapitalCityBuildingQueue::create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'costs' => [
                    'stone' => 1000,
                    'clay' => 1000,
                    'wood' => 1000,
                    'iron' => 1000,
                    'steel' => 0,
                    'population' => 100,
                ],
                'type' => 'upgrade',
                'missing_costs' => [],
                'secondary_status' => CapitalCityQueueStatus::PROCESSING,
                'from_level' => 1,
                'to_level' => 3,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::PROCESSING,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $this->capitalCityBuildingManagement->processBuildingRequest($capitalCityBuildingQueue);

        $kingdomLog = KingdomLog::where('character_id', $character->id)->latest('id')->first();

        $this->assertSame(1, $building->refresh()->level);
        $this->assertNull(CapitalCityBuildingQueue::find($capitalCityBuildingQueue->id));
        $this->assertSame(CapitalCityQueueStatus::REJECTED, $kingdomLog->additional_details['building_data'][0]['status']);
        $this->assertSame(5000, $kingdom->refresh()->current_wood);
        $this->assertSame(5000, $kingdom->refresh()->current_clay);
        $this->assertSame(5000, $kingdom->refresh()->current_stone);
        $this->assertSame(5000, $kingdom->refresh()->current_iron);
        $this->assertSame(5000, $kingdom->refresh()->current_population);
    }
}
