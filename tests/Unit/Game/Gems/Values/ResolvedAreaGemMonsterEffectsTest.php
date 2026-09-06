<?php

namespace Tests\Unit\Game\Gems\Values;

use App\Game\Gems\Values\AreaGemMonsterEffect;
use App\Game\Gems\Values\ResolvedAreaGemAtonement;
use App\Game\Gems\Values\ResolvedAreaGemMonsterEffects;
use Tests\TestCase;

class ResolvedAreaGemMonsterEffectsTest extends TestCase
{
    public function test_effect_resolves_correct_value_for_requested_monster_effect(): void
    {
        $monsterEffects = new ResolvedAreaGemMonsterEffects(
            enemyStrengthIncrease: 0.1,
            enemyHealingIncrease: 0.2,
            enemySpellEvasion: 0.3,
            enemyAffixResistance: 0.4,
            enemyEntrancingChance: 0.5,
            enemyDevouringLightChance: 0.6,
            enemyDevouringDarknessChance: 0.7,
            enemyAmbushChance: 0.8,
            enemyAmbushResistance: 0.9,
            enemyCounterChance: 1.0,
            enemyCounterResistance: 1.1,
            atonement: ResolvedAreaGemAtonement::none(),
        );

        $this->assertSame(0.1, $monsterEffects->effect(AreaGemMonsterEffect::ENEMY_STRENGTH_INCREASE));
        $this->assertSame(0.8, $monsterEffects->effect(AreaGemMonsterEffect::ENEMY_AMBUSH_CHANCE));
        $this->assertSame(1.1, $monsterEffects->effect(AreaGemMonsterEffect::ENEMY_COUNTER_RESISTANCE));
    }

    public function test_has_any_returns_true_when_a_monster_effect_is_positive(): void
    {
        $monsterEffects = new ResolvedAreaGemMonsterEffects(
            enemyStrengthIncrease: 0.15,
            enemyHealingIncrease: 0.0,
            enemySpellEvasion: 0.0,
            enemyAffixResistance: 0.0,
            enemyEntrancingChance: 0.0,
            enemyDevouringLightChance: 0.0,
            enemyDevouringDarknessChance: 0.0,
            enemyAmbushChance: 0.0,
            enemyAmbushResistance: 0.0,
            enemyCounterChance: 0.0,
            enemyCounterResistance: 0.0,
            atonement: ResolvedAreaGemAtonement::none(),
        );

        $this->assertTrue($monsterEffects->hasAny());
    }

    public function test_has_any_returns_true_when_atonement_has_effect(): void
    {
        $monsterEffects = new ResolvedAreaGemMonsterEffects(
            enemyStrengthIncrease: 0.0,
            enemyHealingIncrease: 0.0,
            enemySpellEvasion: 0.0,
            enemyAffixResistance: 0.0,
            enemyEntrancingChance: 0.0,
            enemyDevouringLightChance: 0.0,
            enemyDevouringDarknessChance: 0.0,
            enemyAmbushChance: 0.0,
            enemyAmbushResistance: 0.0,
            enemyCounterChance: 0.0,
            enemyCounterResistance: 0.0,
            atonement: new ResolvedAreaGemAtonement(0, 0.25),
        );

        $this->assertTrue($monsterEffects->hasAny());
    }

    public function test_to_array_preserves_legacy_cache_keys(): void
    {
        $monsterEffects = new ResolvedAreaGemMonsterEffects(
            enemyStrengthIncrease: 0.1,
            enemyHealingIncrease: 0.2,
            enemySpellEvasion: 0.3,
            enemyAffixResistance: 0.4,
            enemyEntrancingChance: 0.5,
            enemyDevouringLightChance: 0.6,
            enemyDevouringDarknessChance: 0.7,
            enemyAmbushChance: 0.8,
            enemyAmbushResistance: 0.9,
            enemyCounterChance: 1.0,
            enemyCounterResistance: 1.1,
            atonement: new ResolvedAreaGemAtonement(0, 0.25),
        );

        $serialized = $monsterEffects->toArray();

        $this->assertSame(0.1, $serialized['enemy_strength_increase']);
        $this->assertSame(1.1, $serialized['enemy_counter_resistance']);
        $this->assertSame(0, $serialized['atonement_type']);
        $this->assertSame(0.25, $serialized['atonement_amount']);
    }
}
