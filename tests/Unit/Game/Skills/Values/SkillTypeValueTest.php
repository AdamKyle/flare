<?php

namespace Tests\Unit\Game\Skills\Values;

use App\Game\Skills\Values\SkillTypeValue;
use Tests\TestCase;

class SkillTypeValueTest extends TestCase
{
    public function test_is_training()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::TRAINING->value)->isTraining());
    }

    public function test_is_crafting()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::CRAFTING->value)->isCrafting());
    }

    public function test_is_enchanting()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::ENCHANTING->value)->isEnchanting());
    }

    public function test_is_disenchanting()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::DISENCHANTING->value)->isDisenchanting());
    }

    public function test_is_alchemy()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::ALCHEMY->value)->isAlchemy());
    }

    public function test_is_battle_timer()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_BATTLE_TIMER->value)->isBattleTimer());
    }

    public function test_is_movement_timer()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_MOVEMENT_TIMER->value)->isMovementTimer());
    }

    public function test_is_directional_timer()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_DIRECTIONAL_MOVE_TIMER->value)->isDirectionalMovementTimer());
    }

    public function test_is_kingdom_building_timer()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_KINGDOM_BUILDING_TIMERS->value)->isKingdomBuildingTimer());
    }

    public function test_is_unit_recruitment_timer()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_UNIT_RECRUITMENT_TIMER->value)->isUnitRecruitmentTimer());
    }

    public function test_is_unit_movement_timer()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_UNIT_MOVEMENT_TIMER->value)->isUnitMovementTimer());
    }

    public function test_is_spell_evasion()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_SPELL_EVASION->value)->isSpellEvasion());
    }

    public function test_is_kingdom()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_KINGDOM->value)->effectsKingdom());
    }

    public function test_effects_class()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::EFFECTS_CLASS->value)->effectsClassSkills());
    }

    public function test_is_gem_crafting()
    {
        $this->assertTrue(SkillTypeValue::tryFrom(SkillTypeValue::GEM_CRAFTING->value)->isGemCrafting());
    }

    public function test_get_named_value()
    {
        $this->assertEquals(
            SkillTypeValue::getValues()[SkillTypeValue::EFFECTS_CLASS->value],
            SkillTypeValue::EFFECTS_CLASS->getNamedValue()
        );
    }
}
