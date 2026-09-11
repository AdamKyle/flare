<?php

namespace App\Game\Gems\Values;

enum AreaGemRewardEffect: string
{
    case CHARACTER_XP_BONUS = 'character_xp_bonus';
    case CHARACTER_CLASS_RANK_XP_BONUS = 'character_class_rank_xp_bonus';
    case KINGDOM_PASSIVE_TRAINING_REDUCTION = 'kingdom_passive_training_reduction';
    case GOLD_GAIN = 'gold_gain';
    case GOLD_DUST_GAIN = 'gold_dust_gain';
    case SHARDS_GAIN = 'shards_gain';
    case COPPER_COIN_GAIN = 'copper_coin_gain';
    case CHARACTER_CLASS_SPECIALTY_XP_GAIN = 'character_class_specialty_xp_gain';
    case ITEM_DROP_CHANCE_INCREASE = 'item_drop_chance_increase';
    case ENEMY_QUEST_ITEM_DROP_CHANCE_INCREASE = 'enemy_quest_item_drop_chance_increase';
    case MONSTER_XP_INCREASE = 'monster_xp_increase';
    case MONSTER_GOLD_DROP_INCREASE = 'monster_gold_drop_increase';

    /**
     * The reward effects that MonsterTransformer bakes into a transformed Monster's own fields.
     *
     * @return array
     */
    public static function monsterTransformedCases(): array
    {
        return [self::MONSTER_XP_INCREASE, self::MONSTER_GOLD_DROP_INCREASE, self::ENEMY_QUEST_ITEM_DROP_CHANCE_INCREASE];
    }
}
