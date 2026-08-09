<?php

namespace Tests\Unit\Game\PassiveSkills\Values;

use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Exception;
use Tests\TestCase;

class PassiveSkillTypeValueTest extends TestCase
{
    public function test_constructor_throws_for_invalid_value(): void
    {
        $this->expectException(Exception::class);

        new PassiveSkillTypeValue(999);
    }

    public function test_is_defence_is_true_for_kingdom_defence(): void
    {
        $value = new PassiveSkillTypeValue(PassiveSkillTypeValue::KINGDOM_DEFENCE);

        $this->assertTrue($value->isDefence());
    }

    public function test_is_resource_gain_is_true_for_kingdom_resource_gain(): void
    {
        $value = new PassiveSkillTypeValue(PassiveSkillTypeValue::KINGDOM_RESOURCE_GAIN);

        $this->assertTrue($value->isResourceGain());
    }

    public function test_is_unit_cost_reduction_is_true_for_kingdom_unit_cost_reduction(): void
    {
        $value = new PassiveSkillTypeValue(PassiveSkillTypeValue::KINGDOM_UNIT_COST_REDUCTION);

        $this->assertTrue($value->isUnitCostReduction());
    }

    public function test_unlocks_building_is_true_for_unlocks_building_type(): void
    {
        $value = new PassiveSkillTypeValue(PassiveSkillTypeValue::UNLOCKS_BUILDING);

        $this->assertTrue($value->unlocksBuilding());
    }

    public function test_is_iron_cost_reduction_is_true_for_iron_cost_reduction(): void
    {
        $value = new PassiveSkillTypeValue(PassiveSkillTypeValue::IRON_COST_REDUCTION);

        $this->assertTrue($value->isIronCostReduction());
    }

    public function test_is_population_cost_reduction_is_true_for_population_cost_reduction(): void
    {
        $value = new PassiveSkillTypeValue(PassiveSkillTypeValue::POPULATION_COST_REDUCTION);

        $this->assertTrue($value->isPopulationCostReduction());
    }

    public function test_is_building_cost_reduction_is_true_for_kingdom_building_cost_reduction(): void
    {
        $value = new PassiveSkillTypeValue(PassiveSkillTypeValue::KINGDOM_BUILDING_COST_REDUCTION);

        $this->assertTrue($value->isBuildingCostReduction());
    }

    public function test_is_steel_smelting_time_reduction_is_true_for_steel_smelting_time_reduction(): void
    {
        $value = new PassiveSkillTypeValue(PassiveSkillTypeValue::STEEL_SMELTING_TIME_REDUCTION);

        $this->assertTrue($value->isSteelSmeltingTimeReduction());
    }

    public function test_is_resource_increase_is_true_for_resource_increase(): void
    {
        $value = new PassiveSkillTypeValue(PassiveSkillTypeValue::RESOURCE_INCREASE);

        $this->assertTrue($value->isResourceIncrease());
    }

    public function test_is_steel_increase_is_true_for_steel_increase(): void
    {
        $value = new PassiveSkillTypeValue(PassiveSkillTypeValue::STEEL_INCREASE);

        $this->assertTrue($value->isSteelIncrease());
    }

    public function test_get_named_value_returns_the_matching_label(): void
    {
        $value = new PassiveSkillTypeValue(PassiveSkillTypeValue::MASTER_FARMER);

        $this->assertSame('Master Farmer', $value->getNamedValue());
    }

    public function test_get_named_values_returns_the_full_map(): void
    {
        $namedValues = PassiveSkillTypeValue::getNamedValues();

        $this->assertSame('Master Steel Smith', $namedValues[PassiveSkillTypeValue::MASTER_STEEL_SMITH]);
    }
}
