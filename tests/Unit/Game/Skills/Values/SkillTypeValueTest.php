<?php

namespace Tests\Unit\Game\Skills\Values;

use App\Game\Skills\Values\SkillTypeValue;
use Tests\TestCase;

class SkillTypeValueTest extends TestCase
{
    public function testIsTraining()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::TRAINING->value)->isTraining());
    }

    public function testIsCrafting()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::CRAFTING->value)->isCrafting());
    }

    public function testIsEnchanting()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::ENCHANTING->value)->isEnchanting());
    }

    public function testIsDisenchanting()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::DISENCHANTING->value)->isDisenchanting());
    }

    public function testIsAlchemy()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::ALCHEMY->value)->isAlchemy());
    }

    public function testIsBattleTimer()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_BATTLE_TIMER->value)->isBattleTimer());
    }

    public function testIsMovementTimer()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_MOVEMENT_TIMER->value)->isMovementTimer());
    }

    public function testIsDirectionalTimer()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_DIRECTIONAL_MOVE_TIMER->value)->isDirectionalMovementTimer());
    }

    public function testIsKingdomBuildingTimer()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_KINGDOM_BUILDING_TIMERS->value)->isKingdomBuildingTimer());
    }

    public function testIsUnitRecruitmentTimer()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_UNIT_RECRUITMENT_TIMER->value)->isUnitRecruitmentTimer());
    }

    public function testIsUnitMovementTimer()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_UNIT_MOVEMENT_TIMER->value)->isUnitMovementTimer());
    }

    public function testIsSpellEvasion()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_SPELL_EVASION->value)->isSpellEvasion());
    }

    public function testIsKingdom()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_KINGDOM->value)->effectsKingdom());
    }

    public function testEffectsClass()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_CLASS->value)->effectsClassSkills());
    }

    public function testIsGemCrafting()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::GEM_CRAFTING->value)->isGemCrafting());
    }

    public function testGetNamedValue()
    {
        $this->assertEquals(
            SkillTypeValue::getValues()[SkillTypeValue::EFFECTS_CLASS->value],
            SkillTypeValue::EFFECTS_CLASS->getNamedValue()
        );
    }
}
