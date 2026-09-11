<?php

namespace App\Game\Gems\Progression\Values;

use App\Flare\Models\Gem;
use App\Game\Gems\Values\AreaGemRewardEffect;
use App\Game\Gems\Values\ResolvedAreaGemSource;

/**
 * Immutable pairing of one resolved Area Gem source with its rolled Gem and
 * the global/personal Gem progression levels that apply to it.
 */
class SourceProgressionContext
{
    public function __construct(
        public readonly ResolvedAreaGemSource $source,
        public readonly Gem $gem,
        public readonly int $globalLevel,
        public readonly int $personalLevel,
    ) {}

    /**
     * Resolve the raw rolled reward field value for the given closed reward effect.
     */
    public function rewardFieldValue(AreaGemRewardEffect $effect): float
    {
        return match ($effect) {
            AreaGemRewardEffect::CHARACTER_XP_BONUS => $this->gem->character_xp_bonus ?? 0.0,
            AreaGemRewardEffect::CHARACTER_CLASS_RANK_XP_BONUS => $this->gem->character_class_rank_xp_bonus ?? 0.0,
            AreaGemRewardEffect::KINGDOM_PASSIVE_TRAINING_REDUCTION => $this->gem->kingdom_passive_training_reduction ?? 0.0,
            AreaGemRewardEffect::GOLD_GAIN => $this->gem->gold_gain ?? 0.0,
            AreaGemRewardEffect::GOLD_DUST_GAIN => $this->gem->gold_dust_gain ?? 0.0,
            AreaGemRewardEffect::SHARDS_GAIN => $this->gem->shards_gain ?? 0.0,
            AreaGemRewardEffect::COPPER_COIN_GAIN => $this->gem->copper_coin_gain ?? 0.0,
            AreaGemRewardEffect::CHARACTER_CLASS_SPECIALTY_XP_GAIN => $this->gem->character_class_specialty_xp_gain ?? 0.0,
            AreaGemRewardEffect::ITEM_DROP_CHANCE_INCREASE => $this->gem->item_drop_chance_increase ?? 0.0,
            AreaGemRewardEffect::ENEMY_QUEST_ITEM_DROP_CHANCE_INCREASE => $this->gem->enemy_quest_item_drop_chance_increase ?? 0.0,
            AreaGemRewardEffect::MONSTER_XP_INCREASE => $this->gem->monster_xp_increase ?? 0.0,
            AreaGemRewardEffect::MONSTER_GOLD_DROP_INCREASE => $this->gem->monster_gold_drop_increase ?? 0.0,
        };
    }
}
