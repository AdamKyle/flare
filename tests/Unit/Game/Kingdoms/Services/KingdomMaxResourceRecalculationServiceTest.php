<?php

namespace Tests\Unit\Game\Kingdoms\Services;

use App\Game\Kingdoms\Service\KingdomMaxResourceRecalculationService;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Setup\Character\CharacterFactory;
use Tests\TestCase;

class KingdomMaxResourceRecalculationServiceTest extends TestCase
{
    use RefreshDatabase;

    public function testRecalculatingMaxResourcesIsIdempotent(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter([], [], true, false)
            ->givePlayerLocation()
            ->createPassiveForCharacter(PassiveSkillTypeValue::RESOURCE_INCREASE, [
                'current_level' => 5,
                'max_level' => 5,
                'resource_bonus_per_level' => 10,
            ]);
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom([
                'max_stone' => 999,
                'max_wood' => 999,
                'max_clay' => 999,
                'max_iron' => 999,
                'max_population' => 999,
            ])
            ->assignBuilding([
                'is_resource_building' => true,
                'increase_stone_amount' => 100,
                'increase_wood_amount' => 0,
                'increase_clay_amount' => 0,
                'increase_iron_amount' => 0,
            ], [
                'level' => 3,
            ])
            ->getKingdom();

        $service = resolve(KingdomMaxResourceRecalculationService::class);
        $service->recalculate($kingdom);
        $firstRecalculation = $kingdom->refresh();
        $service->recalculate($firstRecalculation);
        $secondRecalculation = $kingdom->refresh();

        $this->assertSame($firstRecalculation->max_stone, $secondRecalculation->max_stone);
        $this->assertSame($firstRecalculation->max_wood, $secondRecalculation->max_wood);
        $this->assertSame($firstRecalculation->max_clay, $secondRecalculation->max_clay);
        $this->assertSame($firstRecalculation->max_iron, $secondRecalculation->max_iron);
        $this->assertSame($firstRecalculation->max_population, $secondRecalculation->max_population);
    }

    public function testOldKingdomWithMaxedBountifulResourcesGetsCorrectCaps(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter([], [], true, false)
            ->givePlayerLocation()
            ->createPassiveForCharacter(PassiveSkillTypeValue::RESOURCE_INCREASE, [
                'current_level' => 10,
                'max_level' => 5,
                'resource_bonus_per_level' => 10,
            ]);
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom([
                'max_stone' => 2000,
                'max_wood' => 2000,
                'max_clay' => 2000,
                'max_iron' => 2000,
                'max_population' => 100,
                'current_stone' => 2100,
                'current_population' => 100,
            ])
            ->getKingdom();

        resolve(KingdomMaxResourceRecalculationService::class)->recalculate($kingdom);

        $kingdom = $kingdom->refresh();

        $this->assertSame(2050, $kingdom->max_stone);
        $this->assertSame(2050, $kingdom->max_wood);
        $this->assertSame(2050, $kingdom->max_clay);
        $this->assertSame(2050, $kingdom->max_iron);
        $this->assertSame(150, $kingdom->max_population);
    }

    public function testBuildingUpgradesDoNotDoubleApplyBountifulResources(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter([], [], true, false)
            ->givePlayerLocation()
            ->createPassiveForCharacter(PassiveSkillTypeValue::RESOURCE_INCREASE, [
                'current_level' => 5,
                'max_level' => 5,
                'resource_bonus_per_level' => 10,
            ]);
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom()
            ->assignBuilding([
                'is_resource_building' => true,
                'increase_stone_amount' => 100,
                'increase_wood_amount' => 0,
                'increase_clay_amount' => 0,
                'increase_iron_amount' => 0,
            ], [
                'level' => 4,
            ])
            ->getKingdom();

        resolve(KingdomMaxResourceRecalculationService::class)->recalculate($kingdom);

        $kingdom = $kingdom->refresh();

        $this->assertSame(5050, $kingdom->max_stone);
        $this->assertSame(2050, $kingdom->max_wood);
    }

    public function testFarmUpgradesPreserveExistingPopulationMath(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter([], [], true, false)
            ->givePlayerLocation()
            ->createPassiveForCharacter(PassiveSkillTypeValue::RESOURCE_INCREASE, [
                'current_level' => 5,
                'max_level' => 5,
                'resource_bonus_per_level' => 10,
            ]);
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom([
                'current_population' => 100,
            ])
            ->assignBuilding([
                'is_farm' => true,
            ], [
                'level' => 4,
            ])
            ->getKingdom();

        resolve(KingdomMaxResourceRecalculationService::class)->recalculate($kingdom);

        $this->assertSame(1350, $kingdom->refresh()->max_population);
    }

    public function testNormalModeRecalculatesExactMaxResourcesWhenCurrentResourcesAreHigher(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter([], [], true, false)
            ->givePlayerLocation()
            ->createPassiveForCharacter(PassiveSkillTypeValue::RESOURCE_INCREASE, [
                'current_level' => 5,
                'max_level' => 5,
                'resource_bonus_per_level' => 10,
            ]);
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom([
                'current_stone' => 9000,
                'current_wood' => 9000,
                'current_clay' => 9000,
                'current_iron' => 9000,
                'current_population' => 100,
            ])
            ->getKingdom();

        resolve(KingdomMaxResourceRecalculationService::class)->recalculate($kingdom);

        $kingdom = $kingdom->refresh();

        $this->assertSame(2050, $kingdom->max_stone);
        $this->assertSame(2050, $kingdom->max_wood);
        $this->assertSame(2050, $kingdom->max_clay);
        $this->assertSame(2050, $kingdom->max_iron);
    }

    public function testRepairModePreservesMaxResourcesAndPopulationAboveCurrentValues(): void
    {
        $characterFactory = (new CharacterFactory)
            ->createBaseCharacter([], [], true, false)
            ->givePlayerLocation()
            ->createPassiveForCharacter(PassiveSkillTypeValue::RESOURCE_INCREASE, [
                'current_level' => 5,
                'max_level' => 5,
                'resource_bonus_per_level' => 10,
            ]);
        $kingdom = $characterFactory->kingdomManagement()
            ->assignKingdom([
                'current_stone' => 9000,
                'current_wood' => 8000,
                'current_clay' => 7000,
                'current_iron' => 6000,
                'current_population' => 2000,
            ])
            ->getKingdom();

        resolve(KingdomMaxResourceRecalculationService::class)->recalculate($kingdom, true);

        $kingdom = $kingdom->refresh();

        $this->assertSame(9000, $kingdom->max_stone);
        $this->assertSame(8000, $kingdom->max_wood);
        $this->assertSame(7000, $kingdom->max_clay);
        $this->assertSame(6000, $kingdom->max_iron);
        $this->assertSame(2000, $kingdom->max_population);
    }
}
