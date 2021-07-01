<?php

namespace Tests\Unit\Game\Skills\Values;

use Tests\TestCase;
use App\Game\Skills\Values\SkillTypeValue;

class SkillTypeValueTest extends TestCase {

    public function testSkillTypeValueThrowsException() {
        $this->expectException(\Exception::class);

        new SkillTypeValue(100);
    }

    public function testIsTraining() {
        $this->assertTrue((new SkillTypeValue(0))->isTraining());
    }

    public function testIsCrafting() {
        $this->assertTrue((new SkillTypeValue(1))->isCrafting());
    }

    public function testIsEnchanting() {
        $this->assertTrue((new SkillTypeValue(2))->isEnchanting());
    }

    public function testIsDisenchanting() {
        $this->assertTrue((new SkillTypeValue(3))->isDisenchanting());
    }

    public function testIsAlchemy() {
        $this->assertTrue((new SkillTypeValue(4))->isAlchemy());
    }

    public function testIsBattleTimer() {
        $this->assertTrue((new SkillTypeValue(5))->isBattleTimer());
    }

    public function testIsDirectionalMoveTimer() {
        $this->assertTrue((new SkillTypeValue(6))->isDirectionalMovementTimer());
    }

    public function testIsMinutesMoveTimer() {
        $this->assertTrue((new SkillTypeValue(7))->isMinuteMovementTimer());
    }

    public function testIsKingdomBuildingTimers() {
        $this->assertTrue((new SkillTypeValue(8))->isKingdomBuildingTimer());
    }

    public function testIsKingdomUnitRecruitmentTimers() {
        $this->assertTrue((new SkillTypeValue(9))->isUnitRecruitmentTimer());
    }

    public function testIsKingdomUnitMovementTimer() {
        $this->assertTrue((new SkillTypeValue(10))->isUnitMovementTimer());
    }

    public function testIsSpellEvasion() {
        $this->assertTrue((new SkillTypeValue(11))->isSpellEvasion());
    }

    public function testIsArtifactAnnulment() {
        $this->assertTrue((new SkillTypeValue(12))->isArtifactAnnulment());
    }

    public function testIsArtifactAnnulmentName() {
        $this->assertEquals('Effects Artifact Annulment', (new SkillTypeValue(12))->getNamedValue());
    }
}
