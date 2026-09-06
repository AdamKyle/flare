<?php

namespace App\Game\Gems\Values;

/**
 * Immutable resolved additive player/reward effects for a single Area Gem
 * context.
 */
class ResolvedAreaGemRewardEffects
{
    /**
     * @param  float  $characterXpBonus  Resolved Character XP bonus.
     * @param  float  $characterClassRankXpBonus  Resolved Class Rank XP bonus.
     * @param  float  $kingdomPassiveTrainingReduction  Resolved Kingdom passive training time reduction.
     * @param  float  $goldGain  Resolved Gold gain bonus.
     * @param  float  $goldDustGain  Resolved Gold Dust gain bonus.
     * @param  float  $shardsGain  Resolved Shards gain bonus.
     * @param  float  $copperCoinGain  Resolved Copper Coin gain bonus.
     * @param  float  $characterClassSpecialtyXpGain  Resolved Class Specialty XP gain bonus.
     * @param  float  $itemDropChanceIncrease  Resolved item drop chance increase.
     * @param  float  $enemyQuestItemDropChanceIncrease  Resolved quest item drop chance increase.
     * @param  float  $monsterXpIncrease  Resolved Monster XP increase.
     * @param  float  $monsterGoldDropIncrease  Resolved Monster Gold drop increase.
     */
    public function __construct(
        private readonly float $characterXpBonus,
        private readonly float $characterClassRankXpBonus,
        private readonly float $kingdomPassiveTrainingReduction,
        private readonly float $goldGain,
        private readonly float $goldDustGain,
        private readonly float $shardsGain,
        private readonly float $copperCoinGain,
        private readonly float $characterClassSpecialtyXpGain,
        private readonly float $itemDropChanceIncrease,
        private readonly float $enemyQuestItemDropChanceIncrease,
        private readonly float $monsterXpIncrease,
        private readonly float $monsterGoldDropIncrease,
    ) {}

    /**
     * Build a no-effect resolved reward effects result.
     */
    public static function none(): self
    {
        return new self(0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0);
    }

    /**
     * Resolve the value for the given closed reward effect.
     */
    public function effect(AreaGemRewardEffect $effect): float
    {
        return match ($effect) {
            AreaGemRewardEffect::CHARACTER_XP_BONUS => $this->characterXpBonus,
            AreaGemRewardEffect::CHARACTER_CLASS_RANK_XP_BONUS => $this->characterClassRankXpBonus,
            AreaGemRewardEffect::KINGDOM_PASSIVE_TRAINING_REDUCTION => $this->kingdomPassiveTrainingReduction,
            AreaGemRewardEffect::GOLD_GAIN => $this->goldGain,
            AreaGemRewardEffect::GOLD_DUST_GAIN => $this->goldDustGain,
            AreaGemRewardEffect::SHARDS_GAIN => $this->shardsGain,
            AreaGemRewardEffect::COPPER_COIN_GAIN => $this->copperCoinGain,
            AreaGemRewardEffect::CHARACTER_CLASS_SPECIALTY_XP_GAIN => $this->characterClassSpecialtyXpGain,
            AreaGemRewardEffect::ITEM_DROP_CHANCE_INCREASE => $this->itemDropChanceIncrease,
            AreaGemRewardEffect::ENEMY_QUEST_ITEM_DROP_CHANCE_INCREASE => $this->enemyQuestItemDropChanceIncrease,
            AreaGemRewardEffect::MONSTER_XP_INCREASE => $this->monsterXpIncrease,
            AreaGemRewardEffect::MONSTER_GOLD_DROP_INCREASE => $this->monsterGoldDropIncrease,
        };
    }

    /**
     * Determine whether this result contributes any positive reward effect.
     */
    public function hasAny(): bool
    {
        $effects = [
            $this->characterXpBonus,
            $this->characterClassRankXpBonus,
            $this->kingdomPassiveTrainingReduction,
            $this->goldGain,
            $this->goldDustGain,
            $this->shardsGain,
            $this->copperCoinGain,
            $this->characterClassSpecialtyXpGain,
            $this->itemDropChanceIncrease,
            $this->enemyQuestItemDropChanceIncrease,
            $this->monsterXpIncrease,
            $this->monsterGoldDropIncrease,
        ];

        foreach ($effects as $effect) {
            if ($effect > 0.0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Serialize this result into the legacy/cache compatible field shape.
     *
     * @return array<string, float>
     */
    public function toArray(): array
    {
        return [
            AreaGemRewardEffect::CHARACTER_XP_BONUS->value => $this->characterXpBonus,
            AreaGemRewardEffect::CHARACTER_CLASS_RANK_XP_BONUS->value => $this->characterClassRankXpBonus,
            AreaGemRewardEffect::KINGDOM_PASSIVE_TRAINING_REDUCTION->value => $this->kingdomPassiveTrainingReduction,
            AreaGemRewardEffect::GOLD_GAIN->value => $this->goldGain,
            AreaGemRewardEffect::GOLD_DUST_GAIN->value => $this->goldDustGain,
            AreaGemRewardEffect::SHARDS_GAIN->value => $this->shardsGain,
            AreaGemRewardEffect::COPPER_COIN_GAIN->value => $this->copperCoinGain,
            AreaGemRewardEffect::CHARACTER_CLASS_SPECIALTY_XP_GAIN->value => $this->characterClassSpecialtyXpGain,
            AreaGemRewardEffect::ITEM_DROP_CHANCE_INCREASE->value => $this->itemDropChanceIncrease,
            AreaGemRewardEffect::ENEMY_QUEST_ITEM_DROP_CHANCE_INCREASE->value => $this->enemyQuestItemDropChanceIncrease,
            AreaGemRewardEffect::MONSTER_XP_INCREASE->value => $this->monsterXpIncrease,
            AreaGemRewardEffect::MONSTER_GOLD_DROP_INCREASE->value => $this->monsterGoldDropIncrease,
        ];
    }
}
