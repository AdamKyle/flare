<?php

namespace Tests\Unit\Game\Skills\Values;

use App\Game\Skills\Values\SkillTypeValue;
use Exception;
use Tests\TestCase;

class SkillTypeValueTest extends TestCase
{
    public function testInvalidEventType()
    {
        $this->expectException(Exception::class);

        new SkillTypeValue(905);
    }

    public function testIsTraining()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::TRAINING))->isTraining());
    }

    public function testIsCrafting()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::CRAFTING))->isCrafting());
    }

    public function testIsEnchanting()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::ENCHANTING))->isEnchanting());
    }

    public function testIsDisenchanting()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::DISENCHANTING))->isDisenchanting());
    }

    public function testIsAlchemy()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::ALCHEMY))->isAlchemy());
    }

    public function testIsBattleTimer()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::EFFECTS_BATTLE_TIMER))->isBattleTimer());
    }

    public function testIsMovementTimer()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::EFFECTS_MOVEMENT_TIMER))->isMovementTimer());
    }

    public function testIsDirectionalTimer()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::EFFECTS_DIRECTIONAL_MOVE_TIMER))->isDirectionalMovementTimer());
    }

    public function testIsKingdomBuildingTimer()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::EFFECTS_KINGDOM_BUILDING_TIMERS))->isKingdomBuildingTimer());
    }

    public function testIsKingdomUnitRecruitmentTimer()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::EFFECTS_UNIT_RECRUITMENT_TIMER))->isUnitRecruitmentTimer());
    }

    public function testIsKingdomUnitMovementTimer()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::EFFECTS_UNIT_MOVEMENT_TIMER))->isUnitMovementTimer());
    }

    public function testIsSpellEvasion()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::EFFECTS_SPELL_EVASION))->isSpellEvasion());
    }

    public function testIsKingdom()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::EFFECTS_KINGDOM))->effectsKingdom());
    }

    public function testEffectsClass()
    {
        $this->assertTrue((new SkillTypeValue(SkillTypeValue::EFFECTS_CLASS))->effectsClassSkills());
    }

    public function testGetNamedValues()
    {
        $this->assertEquals(SkillTypeValue::getValues(), SkillTypeValue::$namedValues);
    }

    public function testGetNamedValue()
    {
        $this->assertEquals((new SkillTypeValue(SkillTypeValue::EFFECTS_CLASS))->getNamedValue(), SkillTypeValue::$namedValues[SkillTypeValue::EFFECTS_CLASS]);
    }
}
