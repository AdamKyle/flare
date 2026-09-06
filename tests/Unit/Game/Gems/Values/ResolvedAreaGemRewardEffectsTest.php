<?php

namespace Tests\Unit\Game\Gems\Values;

use App\Game\Gems\Values\AreaGemRewardEffect;
use App\Game\Gems\Values\ResolvedAreaGemRewardEffects;
use Tests\TestCase;

class ResolvedAreaGemRewardEffectsTest extends TestCase
{
    public function test_effect_resolves_correct_value_for_requested_reward_effect(): void
    {
        $rewardEffects = new ResolvedAreaGemRewardEffects(
            characterXpBonus: 0.1,
            characterClassRankXpBonus: 0.2,
            kingdomPassiveTrainingReduction: 0.3,
            goldGain: 0.4,
            goldDustGain: 0.5,
            shardsGain: 0.6,
            copperCoinGain: 0.7,
            characterClassSpecialtyXpGain: 0.8,
            itemDropChanceIncrease: 0.9,
            enemyQuestItemDropChanceIncrease: 1.0,
            monsterXpIncrease: 1.1,
            monsterGoldDropIncrease: 1.2,
        );

        $this->assertSame(0.1, $rewardEffects->effect(AreaGemRewardEffect::CHARACTER_XP_BONUS));
        $this->assertSame(0.7, $rewardEffects->effect(AreaGemRewardEffect::COPPER_COIN_GAIN));
        $this->assertSame(1.2, $rewardEffects->effect(AreaGemRewardEffect::MONSTER_GOLD_DROP_INCREASE));
    }

    public function test_has_any_returns_true_when_a_reward_effect_is_positive(): void
    {
        $rewardEffects = new ResolvedAreaGemRewardEffects(
            characterXpBonus: 0.0,
            characterClassRankXpBonus: 0.0,
            kingdomPassiveTrainingReduction: 0.0,
            goldGain: 0.2,
            goldDustGain: 0.0,
            shardsGain: 0.0,
            copperCoinGain: 0.0,
            characterClassSpecialtyXpGain: 0.0,
            itemDropChanceIncrease: 0.0,
            enemyQuestItemDropChanceIncrease: 0.0,
            monsterXpIncrease: 0.0,
            monsterGoldDropIncrease: 0.0,
        );

        $this->assertTrue($rewardEffects->hasAny());
    }

    public function test_to_array_preserves_expected_serialized_keys(): void
    {
        $rewardEffects = new ResolvedAreaGemRewardEffects(
            characterXpBonus: 0.1,
            characterClassRankXpBonus: 0.2,
            kingdomPassiveTrainingReduction: 0.3,
            goldGain: 0.4,
            goldDustGain: 0.5,
            shardsGain: 0.6,
            copperCoinGain: 0.7,
            characterClassSpecialtyXpGain: 0.8,
            itemDropChanceIncrease: 0.9,
            enemyQuestItemDropChanceIncrease: 1.0,
            monsterXpIncrease: 1.1,
            monsterGoldDropIncrease: 1.2,
        );

        $serialized = $rewardEffects->toArray();

        $this->assertSame(0.1, $serialized['character_xp_bonus']);
        $this->assertSame(0.3, $serialized['kingdom_passive_training_reduction']);
        $this->assertSame(1.2, $serialized['monster_gold_drop_increase']);
    }
}
