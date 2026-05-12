<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Game\Kingdoms\Service\KingdomBuildingService;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomBuildingServiceTest extends TestCase
{
    use RefreshDatabase;

    private ?CharacterFactory $character;

    private ?KingdomBuildingService $kingdomBuildingService;

    public function setUp(): void
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

    public function tearDown(): void
    {
        parent::tearDown();

        $this->character = null;
        $this->kingdomBuildingService = null;
    }

    public function testItConsumesDiscountedResourceCostsWhenTheBuildingManagementPassiveIsPartiallyTrained(): void
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
}