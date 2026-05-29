<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Flare\Models\BuildingInQueue;
use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\Kingdoms\Values\BuildingQueueType;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomBuildingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?CharacterFactory $character;

    private ?KingdomBuildingService $kingdomBuildingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->character = (new CharacterFactory)
            ->createBaseCharacter([], [], true, false)
            ->givePlayerLocation();

        $this->character
            ->passiveSkillManagement()
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
            ]);

        $this->kingdomBuildingService = resolve(KingdomBuildingService::class);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->kingdomBuildingService = null;
    }

    public function test_it_consumes_discounted_resource_costs_when_the_building_management_passive_is_partially_trained(): void
    {
        $kingdomManagement = $this->character
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

        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();

        $this->kingdomBuildingService->updateKingdomResourcesForKingdomBuildingUpgrade($building);

        $kingdom = $kingdom->refresh();

        $this->assertSame(7130, $kingdom->current_stone);
        $this->assertSame(8852, $kingdom->current_clay);
        $this->assertSame(7704, $kingdom->current_wood);
        $this->assertSame(8278, $kingdom->current_iron);
        $this->assertSame(9720, $kingdom->current_population);
    }

    public function test_pending_queue_with_refundable_time_left_refunds_resources_up_to_kingdom_max_and_deletes_queue(): void
    {
        $kingdomManagement = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_stone' => 950,
                'current_clay' => 950,
                'current_wood' => 950,
                'current_iron' => 950,
                'current_steel' => 0,
                'current_population' => 950,
                'max_stone' => 1000,
                'max_clay' => 1000,
                'max_wood' => 1000,
                'max_iron' => 1000,
                'max_steel' => 0,
                'max_population' => 1000,
            ])
            ->assignBuilding([
                'stone_cost' => 100,
                'clay_cost' => 100,
                'wood_cost' => 100,
                'iron_cost' => 100,
                'steel_cost' => 0,
                'required_population' => 100,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'from_level' => 1,
            'to_level' => 2,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subMinutes(10),
            'completed_at' => now()->addMinutes(90),
        ]);

        $result = $this->kingdomBuildingService->cancelKingdomBuildingUpgrade($queue);

        $kingdom = $kingdom->refresh();

        $this->assertTrue($result);
        $this->assertNull(BuildingInQueue::find($queue->id));
        $this->assertSame(1000, $kingdom->current_stone);
        $this->assertSame(1000, $kingdom->current_clay);
        $this->assertSame(1000, $kingdom->current_wood);
        $this->assertSame(1000, $kingdom->current_iron);
        $this->assertSame(0, $kingdom->current_steel);
        $this->assertSame(1000, $kingdom->current_population);
    }

    public function test_complete_queue_returns_false_leaves_resources_unchanged_and_does_not_delete_queue(): void
    {
        $kingdomManagement = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_stone' => 500,
                'current_clay' => 500,
                'current_wood' => 500,
                'current_iron' => 500,
                'current_steel' => 0,
                'current_population' => 500,
                'max_stone' => 1000,
                'max_clay' => 1000,
                'max_wood' => 1000,
                'max_iron' => 1000,
                'max_steel' => 0,
                'max_population' => 1000,
            ])
            ->assignBuilding([
                'stone_cost' => 100,
                'clay_cost' => 100,
                'wood_cost' => 100,
                'iron_cost' => 100,
                'steel_cost' => 0,
                'required_population' => 100,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'from_level' => 1,
            'to_level' => 2,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subMinutes(100),
            'completed_at' => now()->subMinute(),
        ]);

        $result = $this->kingdomBuildingService->cancelKingdomBuildingUpgrade($queue);

        $kingdom = $kingdom->refresh();

        $this->assertFalse($result);
        $this->assertNotNull(BuildingInQueue::find($queue->id));
        $this->assertSame(500, $kingdom->current_stone);
        $this->assertSame(500, $kingdom->current_clay);
        $this->assertSame(500, $kingdom->current_wood);
        $this->assertSame(500, $kingdom->current_iron);
        $this->assertSame(0, $kingdom->current_steel);
        $this->assertSame(500, $kingdom->current_population);
    }

    public function test_refund_percent_below_ten_percent_returns_false_leaves_resources_unchanged_and_does_not_delete_queue(): void
    {
        $kingdomManagement = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_stone' => 500,
                'current_clay' => 500,
                'current_wood' => 500,
                'current_iron' => 500,
                'current_steel' => 0,
                'current_population' => 500,
                'max_stone' => 1000,
                'max_clay' => 1000,
                'max_wood' => 1000,
                'max_iron' => 1000,
                'max_steel' => 0,
                'max_population' => 1000,
            ])
            ->assignBuilding([
                'stone_cost' => 100,
                'clay_cost' => 100,
                'wood_cost' => 100,
                'iron_cost' => 100,
                'steel_cost' => 0,
                'required_population' => 100,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'from_level' => 1,
            'to_level' => 2,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subMinutes(95),
            'completed_at' => now()->addMinutes(5),
        ]);

        $result = $this->kingdomBuildingService->cancelKingdomBuildingUpgrade($queue);

        $kingdom = $kingdom->refresh();

        $this->assertFalse($result);
        $this->assertNotNull(BuildingInQueue::find($queue->id));
        $this->assertSame(500, $kingdom->current_stone);
        $this->assertSame(500, $kingdom->current_clay);
        $this->assertSame(500, $kingdom->current_wood);
        $this->assertSame(500, $kingdom->current_iron);
        $this->assertSame(0, $kingdom->current_steel);
        $this->assertSame(500, $kingdom->current_population);
    }

    public function test_repeated_failed_cancellation_cannot_make_any_current_resource_negative(): void
    {
        $kingdomManagement = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_stone' => 5,
                'current_clay' => 5,
                'current_wood' => 5,
                'current_iron' => 5,
                'current_steel' => 0,
                'current_population' => 5,
                'max_stone' => 1000,
                'max_clay' => 1000,
                'max_wood' => 1000,
                'max_iron' => 1000,
                'max_steel' => 0,
                'max_population' => 1000,
            ])
            ->assignBuilding([
                'stone_cost' => 100,
                'clay_cost' => 100,
                'wood_cost' => 100,
                'iron_cost' => 100,
                'steel_cost' => 0,
                'required_population' => 100,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'from_level' => 1,
            'to_level' => 2,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now()->subMinutes(100),
            'completed_at' => now()->subMinute(),
        ]);

        $firstResult = $this->kingdomBuildingService->cancelKingdomBuildingUpgrade($queue);
        $secondResult = $this->kingdomBuildingService->cancelKingdomBuildingUpgrade($queue->refresh());

        $kingdom = $kingdom->refresh();

        $this->assertFalse($firstResult);
        $this->assertFalse($secondResult);
        $this->assertNotNull(BuildingInQueue::find($queue->id));
        $this->assertSame(5, $kingdom->current_stone);
        $this->assertSame(5, $kingdom->current_clay);
        $this->assertSame(5, $kingdom->current_wood);
        $this->assertSame(5, $kingdom->current_iron);
        $this->assertSame(0, $kingdom->current_steel);
        $this->assertSame(5, $kingdom->current_population);
    }

    public function test_invalid_cancellation_timing_returns_false_leaves_resources_unchanged_and_does_not_delete_queue(): void
    {
        $kingdomManagement = $this->character
            ->kingdomManagement()
            ->assignKingdom([
                'current_stone' => 500,
                'current_clay' => 500,
                'current_wood' => 500,
                'current_iron' => 500,
                'current_steel' => 0,
                'current_population' => 500,
                'max_stone' => 1000,
                'max_clay' => 1000,
                'max_wood' => 1000,
                'max_iron' => 1000,
                'max_steel' => 0,
                'max_population' => 1000,
            ])
            ->assignBuilding([
                'stone_cost' => 100,
                'clay_cost' => 100,
                'wood_cost' => 100,
                'iron_cost' => 100,
                'steel_cost' => 0,
                'required_population' => 100,
            ], [
                'level' => 1,
            ]);
        $character = $kingdomManagement->getCharacter();
        $kingdom = $kingdomManagement->getKingdom();
        $building = $kingdom->buildings()->first();
        $queue = BuildingInQueue::factory()->create([
            'character_id' => $character->id,
            'kingdom_id' => $kingdom->id,
            'building_id' => $building->id,
            'from_level' => 1,
            'to_level' => 2,
            'type' => BuildingQueueType::UPGRADE,
            'started_at' => now(),
            'completed_at' => now(),
        ]);

        $result = $this->kingdomBuildingService->cancelKingdomBuildingUpgrade($queue);

        $kingdom = $kingdom->refresh();

        $this->assertFalse($result);
        $this->assertNotNull(BuildingInQueue::find($queue->id));
        $this->assertSame(500, $kingdom->current_stone);
        $this->assertSame(500, $kingdom->current_clay);
        $this->assertSame(500, $kingdom->current_wood);
        $this->assertSame(500, $kingdom->current_iron);
        $this->assertSame(0, $kingdom->current_steel);
        $this->assertSame(500, $kingdom->current_population);
    }
}
