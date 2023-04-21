<?php

namespace Tests\Unit\Game\PassiveSkills\Values;

use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Tests\TestCase;

class PassiveSkillTypeValueTest extends TestCase {

    use RefreshDatabase;

    public function setUp(): void {
        parent::setUp();
    }

    public function tearDown(): void {
        parent::tearDown();
    }

    public function testExpectsErrorForInvalidType() {
        $this->expectException(Exception::class);

        new PassiveSkillTypeValue(-10);
    }

    public function testIsDefencePassiveType() {
        $this->assertTrue((new PassiveSkillTypeValue(0))->isDefence());
    }

    public function testIsResourceGainPassiveType() {
        $this->assertTrue((new PassiveSkillTypeValue(1))->isResourceGain());
    }

    public function testIsUnitCostReductionPassiveType() {
        $this->assertTrue((new PassiveSkillTypeValue(2))->isUnitCostReduction());
    }

    public function testIsBuildingCostReductionPassiveType() {
        $this->assertTrue((new PassiveSkillTypeValue(3))->isBuildingCostReduction());
    }

    public function testUnlocksBuildingPassiveType() {
        $this->assertTrue((new PassiveSkillTypeValue(4))->unlocksBuilding());
    }

    public function testIsIronCostReductionPassiveType() {
        $this->assertTrue((new PassiveSkillTypeValue(5))->isIronCostReduction());
    }

    public function testIsPopulationCostReductionPassiveType() {
        $this->assertTrue((new PassiveSkillTypeValue(6))->isPopulationCostReduction());
    }

    public function testSteelSmeltingTimeReduction() {
        $this->assertTrue((new PassiveSkillTypeValue(7))->isSteelSmeltingTimeReduction());
    }

    public function testIsIronCostReductionPassiveTypeName() {
        $this->assertEquals('Iron Cost Reduction', (new PassiveSkillTypeValue(5))->getNamedValue());
    }

    public function testGetNamedValues() {
        $this->assertNotEmpty(PassiveSkillTypeValue::getNamedValues());
    }
}
