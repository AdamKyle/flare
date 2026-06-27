<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\BuildingInQueue;
use App\Flare\Models\CapitalCityBuildingQueue;
use App\Flare\Models\KingdomLog;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueRequest;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingQueueTable;
use App\Game\Kingdoms\Events\UpdateCapitalCityBuildingUpgrades;
use App\Game\Kingdoms\Handlers\CapitalCityHandlers\CapitalCityRequestResourcesHandler;
use App\Game\Kingdoms\Jobs\CapitalCityResourceRequest;
use App\Game\Kingdoms\Service\CapitalCityBuildingManagement;
use App\Game\Kingdoms\Values\BuildingCosts;
use App\Game\Kingdoms\Values\CapitalCityQueueStatus;
use App\Game\Kingdoms\Values\CapitalCityResourceRequestType;
use App\Game\Kingdoms\Values\UnitNames;
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

    protected function setUp(): void
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

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->capitalCityBuildingManagement = null;
    }

    public function test_it_consumes_discounted_resource_costs_for_one_capital_city_building_upgrade_when_the_building_management_passive_is_partially_trained(): void
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

        $targetKingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();

        $this->capitalCityBuildingManagement->processBuildingRequest($capitalCityBuildingQueue);

        $targetKingdom = $targetKingdom->refresh();

        $this->assertSame(7130, $targetKingdom->current_stone);
        $this->assertSame(8852, $targetKingdom->current_clay);
        $this->assertSame(7704, $targetKingdom->current_wood);
        $this->assertSame(8278, $targetKingdom->current_iron);
        $this->assertSame(9720, $targetKingdom->current_population);
    }

    public function test_it_consumes_discounted_resource_costs_for_multiple_capital_city_building_upgrades_when_the_building_management_passive_is_partially_trained(): void
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

        $targetKingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();

        $this->capitalCityBuildingManagement->processBuildingRequest($capitalCityBuildingQueue);

        $targetKingdom = $targetKingdom->refresh();

        $this->assertSame(4260, $targetKingdom->current_stone);
        $this->assertSame(7704, $targetKingdom->current_clay);
        $this->assertSame(5408, $targetKingdom->current_wood);
        $this->assertSame(6556, $targetKingdom->current_iron);
        $this->assertSame(9440, $targetKingdom->current_population);
    }

    public function test_capital_city_building_resource_rejection_updates_building_request_data_and_top_level_status(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $kingdomManagement = $this->character
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

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

    public function test_capital_city_building_resource_dispatch_uses_long_running_connection(): void
    {
        Event::fake();
        Queue::fake();

        $character = $this->character->getCharacter();
        $this->character
            ->passiveSkillManagement()
            ->assignPassiveSkill(PassiveSkillTypeValue::RESOURCE_REQUEST_TIME_REDUCTION, 0, [
                'name' => 'Resource Request Time Reduction',
                'resource_request_time_reduction' => 0.0,
                'max_level' => 5,
            ]);
        $requestingKingdomManagement = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_stone' => 0,
                'x_position' => 16,
                'y_position' => 16,
            ])
            ->assignBuilding(['name' => BuildingCosts::MARKET_PLACE], ['level' => 5]);
        $requestingKingdom = $requestingKingdomManagement->getKingdom();
        $providingKingdom = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_stone' => 500,
                'current_population' => 100,
                'x_position' => 32,
                'y_position' => 16,
            ])
            ->assignBuilding(['name' => BuildingCosts::MARKET_PLACE], ['level' => 5])
            ->assignUnits(['name' => UnitNames::SPEARMEN], 75)
            ->getKingdom();
        $building = $requestingKingdom->buildings->first();
        $requestingKingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $requestingKingdom->id,
            'requested_kingdom' => $providingKingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
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
        $capitalCityBuildingQueue = $requestingKingdomManagement->getCapitalCityBuildingQueue();

        resolve(CapitalCityRequestResourcesHandler::class)->handleResourceRequests(
            $capitalCityBuildingQueue,
            $character,
            ['stone' => 100],
            $capitalCityBuildingQueue->building_request_data,
            $requestingKingdom,
            CapitalCityResourceRequestType::BUILDING_QUEUE,
        );

        Queue::assertPushed(CapitalCityResourceRequest::class, function (CapitalCityResourceRequest $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }

    public function test_capital_city_resource_request_redispatches_when_queue_is_waiting_on_long_running_connection(): void
    {
        Queue::fake();

        $character = $this->character->getCharacter();
        $kingdomManagement = $this->character
            ->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'requested_kingdom' => $kingdom->id,
            'building_request_data' => [[
                'building_id' => $building->id,
                'building_name' => $building->name,
                'missing_costs' => ['stone' => 100],
                'secondary_status' => CapitalCityQueueStatus::REQUESTING,
                'from_level' => 1,
                'to_level' => 2,
                'type' => 'upgrade',
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::REQUESTING,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

        $job = new CapitalCityResourceRequest(
            $capitalCityBuildingQueue->id,
            999,
            CapitalCityResourceRequestType::BUILDING_QUEUE,
        );

        $this->app->call([$job, 'handle']);

        Queue::assertPushed(CapitalCityResourceRequest::class, function (CapitalCityResourceRequest $job) {
            return $job->connection === 'long_running' && $job->queue === 'default_long';
        });
    }

    public function test_mixed_valid_and_max_level_building_request_skips_max_level_without_spending_for_it(): void
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

        $this->assertSame(0, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
        $this->assertSame(5000, $targetKingdom->refresh()->current_wood);
        $this->assertSame(5000, $targetKingdom->refresh()->current_clay);
        $this->assertSame(5000, $targetKingdom->refresh()->current_stone);
        $this->assertSame(5000, $targetKingdom->refresh()->current_iron);
        $this->assertSame(5000, $targetKingdom->refresh()->current_population);
    }

    public function test_cancellation_rejected_capital_city_building_queue_does_not_block_new_request(): void
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
        $targetKingdomManagement = $this->character
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
            ]);
        $targetKingdom = $targetKingdomManagement->getKingdom();
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
                'from_level' => 1,
                'to_level' => 2,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::CANCELLATION_REJECTED,
            'started_at' => now()->subHour(),
            'completed_at' => now()->subMinute(),
        ]);

        $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $capitalCity, [[
            'kingdomId' => $targetKingdom->id,
            'buildingIds' => [$building->id],
        ]], 'upgrade');

        $capitalCityBuildingQueue = CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)
            ->where('status', CapitalCityQueueStatus::TRAVELING)
            ->first();

        $this->assertNotNull($capitalCityBuildingQueue);
        $this->assertSame($building->id, $capitalCityBuildingQueue->building_request_data[0]['building_id']);
    }

    public function test_no_resources_are_spent_for_rejected_max_level_buildings_during_processing(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $kingdomManagement = $this->character
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
            ]);
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

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

    public function test_stale_processing_path_rejects_and_does_not_spend_resources(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $kingdomManagement = $this->character
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
            ]);
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

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

    public function test_mass_upgrade_request_creates_one_queue_per_kingdom(): void
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
        $firstTargetKingdom = $this->character
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
            ->getKingdom();
        $secondTargetKingdom = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 5000,
                'current_clay' => 5000,
                'current_stone' => 5000,
                'current_iron' => 5000,
                'current_population' => 5000,
                'x_position' => 48,
                'y_position' => 16,
            ])
            ->assignBuilding([
                'name' => BuildingCosts::FARM,
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
            ->getKingdom();
        $firstBuilding = $firstTargetKingdom->buildings()->first();
        $secondBuilding = $secondTargetKingdom->buildings()->first();

        $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $capitalCity, [
            ['kingdomId' => $firstTargetKingdom->id, 'buildingIds' => [$firstBuilding->id]],
            ['kingdomId' => $secondTargetKingdom->id, 'buildingIds' => [$secondBuilding->id]],
        ], 'upgrade');

        $this->assertSame(2, CapitalCityBuildingQueue::count());
    }

    public function test_mass_upgrade_request_sets_completed_at_after_started_at(): void
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
            ->getKingdom();
        $building = $targetKingdom->buildings()->first();

        $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $capitalCity, [
            ['kingdomId' => $targetKingdom->id, 'buildingIds' => [$building->id]],
        ], 'upgrade');

        $queue = CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->first();

        $this->assertNotNull($queue);
        $this->assertTrue($queue->completed_at->greaterThan($queue->started_at));
    }

    public function test_manual_building_queue_blocks_building_from_capital_city_request(): void
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
            ->getKingdom();
        $building = $targetKingdom->buildings()->first();

        BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $targetKingdom->id,
            'building_id' => $building->id,
            'to_level' => 2,
            'type' => 0,
            'started_at' => now(),
            'completed_at' => now()->addHour(),
        ]);

        $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $capitalCity, [
            ['kingdomId' => $targetKingdom->id, 'buildingIds' => [$building->id]],
        ], 'upgrade');

        $this->assertSame(0, CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->count());
    }

    public function test_active_traveling_capital_city_queue_blocks_same_building_from_new_request(): void
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
        $targetKingdomManagement = $this->character
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
            ]);
        $targetKingdom = $targetKingdomManagement->getKingdom();
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
                'from_level' => 1,
                'to_level' => 2,
            ]],
            'messages' => [],
            'status' => CapitalCityQueueStatus::TRAVELING,
            'started_at' => now(),
            'completed_at' => now()->addMinutes(10),
        ]);
        $existingQueue = $targetKingdomManagement->getCapitalCityBuildingQueue();

        $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $capitalCity, [
            ['kingdomId' => $targetKingdom->id, 'buildingIds' => [$building->id]],
        ], 'upgrade');

        $queues = CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->get();
        $this->assertCount(1, $queues);
        $this->assertSame($existingQueue->id, $queues->first()->id);
    }

    public function test_building_upgrade_events_fire_once_for_mass_request_across_multiple_kingdoms(): void
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
        $firstTargetKingdom = $this->character
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
            ->getKingdom();
        $secondTargetKingdom = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 5000,
                'current_clay' => 5000,
                'current_stone' => 5000,
                'current_iron' => 5000,
                'current_population' => 5000,
                'x_position' => 48,
                'y_position' => 16,
            ])
            ->assignBuilding([
                'name' => BuildingCosts::FARM,
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
            ->getKingdom();
        $firstBuilding = $firstTargetKingdom->buildings()->first();
        $secondBuilding = $secondTargetKingdom->buildings()->first();

        $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $capitalCity, [
            ['kingdomId' => $firstTargetKingdom->id, 'buildingIds' => [$firstBuilding->id]],
            ['kingdomId' => $secondTargetKingdom->id, 'buildingIds' => [$secondBuilding->id]],
        ], 'upgrade');

        Event::assertDispatchedTimes(UpdateCapitalCityBuildingUpgrades::class, 1);
        Event::assertDispatchedTimes(UpdateCapitalCityBuildingQueueTable::class, 1);
    }

    public function test_building_upgrade_per_kingdom_progress_events_fire_for_mass_request(): void
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
        $firstTargetKingdom = $this->character
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
            ->getKingdom();
        $secondTargetKingdom = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 5000,
                'current_clay' => 5000,
                'current_stone' => 5000,
                'current_iron' => 5000,
                'current_population' => 5000,
                'x_position' => 48,
                'y_position' => 16,
            ])
            ->assignBuilding([
                'name' => BuildingCosts::FARM,
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
            ->getKingdom();
        $firstBuilding = $firstTargetKingdom->buildings()->first();
        $secondBuilding = $secondTargetKingdom->buildings()->first();

        $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $capitalCity, [
            ['kingdomId' => $firstTargetKingdom->id, 'buildingIds' => [$firstBuilding->id]],
            ['kingdomId' => $secondTargetKingdom->id, 'buildingIds' => [$secondBuilding->id]],
        ], 'upgrade');

        Event::assertDispatchedTimes(UpdateCapitalCityBuildingQueueRequest::class, 2);
    }

    public function test_building_repair_per_kingdom_progress_events_fire_for_mass_request(): void
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
        $firstTargetKingdom = $this->character
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
                'current_durability' => 1,
                'max_durability' => 100,
            ])
            ->getKingdom();
        $secondTargetKingdom = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_wood' => 5000,
                'current_clay' => 5000,
                'current_stone' => 5000,
                'current_iron' => 5000,
                'current_population' => 5000,
                'x_position' => 48,
                'y_position' => 16,
            ])
            ->assignBuilding([
                'name' => BuildingCosts::FARM,
                'max_level' => 5,
                'stone_cost' => 100,
                'clay_cost' => 100,
                'wood_cost' => 100,
                'iron_cost' => 100,
                'steel_cost' => 0,
                'required_population' => 10,
            ], [
                'current_durability' => 1,
                'max_durability' => 100,
            ])
            ->getKingdom();
        $firstBuilding = $firstTargetKingdom->buildings()->first();
        $secondBuilding = $secondTargetKingdom->buildings()->first();

        $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $capitalCity, [
            ['kingdomId' => $firstTargetKingdom->id, 'buildingIds' => [$firstBuilding->id]],
            ['kingdomId' => $secondTargetKingdom->id, 'buildingIds' => [$secondBuilding->id]],
        ], 'repair');

        Event::assertDispatchedTimes(UpdateCapitalCityBuildingQueueRequest::class, 2);
    }

    public function test_mass_upgrade_request_with_multiple_buildings_per_kingdom_creates_queue_containing_all_eligible_buildings(): void
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
            ->getKingdom();
        $buildings = $targetKingdom->buildings()->orderBy('id')->get();
        $firstBuilding = $buildings->first();
        $secondBuilding = $buildings->last();

        $this->capitalCityBuildingManagement->createBuildingUpgradeRequestQueue($character, $capitalCity, [
            ['kingdomId' => $targetKingdom->id, 'buildingIds' => [$firstBuilding->id, $secondBuilding->id]],
        ], 'upgrade');

        $queue = CapitalCityBuildingQueue::where('kingdom_id', $targetKingdom->id)->first();
        $this->assertNotNull($queue);
        $this->assertCount(2, $queue->building_request_data);
        $buildingIds = array_column($queue->building_request_data, 'building_id');
        $this->assertContains($firstBuilding->id, $buildingIds);
        $this->assertContains($secondBuilding->id, $buildingIds);
    }

    public function test_over_max_queue_data_rejects_during_processing_without_spending_or_mutating(): void
    {
        Event::fake();

        $character = $this->character->getCharacter();
        $kingdomManagement = $this->character
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
            ]);
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $kingdomManagement->assignCapitalCityBuildingQueue([
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
        $capitalCityBuildingQueue = $kingdomManagement->getCapitalCityBuildingQueue();

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
