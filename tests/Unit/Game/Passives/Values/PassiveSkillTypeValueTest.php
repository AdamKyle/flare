<?php

namespace Tests\Unit\Game\Passives\Values;

use App\Game\PassiveSkills\Values\PassiveSkillTypeValue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PassiveSkillTypeValueTest extends TestCase {
    use RefreshDatabase;

    public function setUp(): void {
        parent::setUp();
    }

    public function testPassiveSkillTypeValueThrowsException() {
        $this->expectException(\Exception::class);

        new PassiveSkillTypeValue(100);
    }

    public function testIsDefence() {
        $this->assertTrue((new PassiveSkillTypeValue(0))->isDefence());
    }

    public function testIsResourceGain() {
        $this->assertTrue((new PassiveSkillTypeValue(1))->isResourceGain());
    }

    public function testUnitCostReduction() {
        $this->assertTrue((new PassiveSkillTypeValue(2))->isUnitCostReduction());
    }

    public function testBuildingCostReduction() {
        $this->assertTrue((new PassiveSkillTypeValue(3))->isBuildingCostReduction());
    }

    public function testUnlocksNewBuilding() {
        $this->assertTrue((new PassiveSkillTypeValue(4))->unlocksBuilding());
    }

    public function testIronCostReduction() {
        $this->assertTrue((new PassiveSkillTypeValue(5))->isIronCostReduction());
    }

    public function testPopulationCostReduction() {
        $this->assertTrue((new PassiveSkillTypeValue(6))->isPopulationCostReduction());
    }

}
