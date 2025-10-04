<?php

namespace Tests\Unit\Game\PassiveSkills\Values;

use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassiveSkillTypeValueTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function test_expects_error_for_invalid_type()
    {
        $this->expectException(Exception::class);

        new PassiveSkillTypeValue(-10);
    }

    public function test_is_defence_passive_type()
    {
        $this->assertTrue((new PassiveSkillTypeValue(0))->isDefence());
    }

    public function test_is_resource_gain_passive_type()
    {
        $this->assertTrue((new PassiveSkillTypeValue(1))->isResourceGain());
    }

    public function test_is_unit_cost_reduction_passive_type()
    {
        $this->assertTrue((new PassiveSkillTypeValue(2))->isUnitCostReduction());
    }

    public function test_is_building_cost_reduction_passive_type()
    {
        $this->assertTrue((new PassiveSkillTypeValue(3))->isBuildingCostReduction());
    }

    public function test_unlocks_building_passive_type()
    {
        $this->assertTrue((new PassiveSkillTypeValue(4))->unlocksBuilding());
    }

    public function test_is_iron_cost_reduction_passive_type()
    {
        $this->assertTrue((new PassiveSkillTypeValue(5))->isIronCostReduction());
    }

    public function test_is_population_cost_reduction_passive_type()
    {
        $this->assertTrue((new PassiveSkillTypeValue(6))->isPopulationCostReduction());
    }

    public function test_steel_smelting_time_reduction()
    {
        $this->assertTrue((new PassiveSkillTypeValue(7))->isSteelSmeltingTimeReduction());
    }

    public function test_is_iron_cost_reduction_passive_type_name()
    {
        $this->assertEquals('Iron Cost Reduction', (new PassiveSkillTypeValue(5))->getNamedValue());
    }

    public function test_get_named_values()
    {
        $this->assertNotEmpty(PassiveSkillTypeValue::getNamedValues());
    }
}
