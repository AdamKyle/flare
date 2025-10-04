<?php

namespace App\Flare\Items\Values;

enum QuestItemEffectsType: string
{
    case WALK_ON_WATER = 'walk-on-water';
    case WALK_ON_DEATH_WATER = 'walk-on-death-water';
    case LABYRINTH = 'labyrinth';
    case DUNGEON = 'dungeon';
    case SHADOW_PLANE = 'shadow-plane';
    case HELL = 'hell';
    case PURGATORY = 'purgatory';
    case TELEPORT_TO_CELESTIAL = 'teleport-to-celestial';
    case AFFIXES_IRRESISTIBLE = 'affixes-irresistible';
    case CONTINUE_LEVELING = 'continue-leveling';
    case GOLD_DUST_RUSH = 'gold-dust-rush';
    case MASS_EMBEZZLE = 'mass-embezzle';
    case WALK_ON_MAGMA = 'walk-on-magma';
    case QUEEN_OF_HEARTS = 'speak-to-queen-of-hearts';
    case FACTION_POINTS = 'effects-faction-points';
    case GET_COPPER_COINS = 'get-copper-coins';
    case ENTER_PURGATORY_HOUSE = 'enter-purgatory-house';
    case HIDE_CHAT_LOCATION = 'hide-chat-location';
    case WALK_ON_ICE = 'walk-on-ice';
    case SETTLE_IN_ICE_PLANE = 'settle-on-the-ice-plane';
    case THE_OLD_CHURCH = 'the-old-church';
    case MERCENARY_SLOT_BONUS = 'mercenary-slot-bonus';
    case WALK_ON_DELUSIONAL_MEMORIES_WATER = 'walk-on-delusional-memories-water';
    case TWISTED_TREE_BRANCH = 'access-twisted-memories';
    case TWISTED_DUNGEONS = 'twisted-dungeons';

    public function label(): string
    {
        return match ($this) {
            self::WALK_ON_WATER => 'Walk on water (Surface and Labyrinth)',
            self::WALK_ON_ICE => 'Walk on Ice (The Ice Plane)',
            self::LABYRINTH => 'Use Traverse (beside movement map-actions) to traverse to Labyrinth plane',
            self::DUNGEON => 'Use Traverse (beside movement map-actions) to traverse to Dungeons plane',
            self::SHADOW_PLANE => 'Use Traverse (beside movement map-actions) to traverse to Shadow Plane',
            self::HELL => 'Use Traverse (beside movement map-actions) to traverse to Hell plane',
            self::PURGATORY => 'Use Traverse (beside movement map-actions) to traverse to Purgatory plane (only while in Hell at Tear in the Fabric of Time: X/Y 208/64)',
            self::MASS_EMBEZZLE => 'Lets you mass embezzle from all kingdoms on the plane. Go to Kingdoms → select a kingdom → Mass Embezzle (not cross-plane).',
            self::WALK_ON_MAGMA => 'Lets you walk on Magma in Hell.',
            self::AFFIXES_IRRESISTIBLE => 'Makes affix damage irresistible except in Hell and Purgatory.',
            self::QUEEN_OF_HEARTS => 'Lets a character approach and speak to the Queen of Hearts in Hell.',
            self::GOLD_DUST_RUSH => 'Provides a small chance to get a gold dust rush when disenchanting.',
            self::WALK_ON_DEATH_WATER => 'Walk on Death Water in Dungeons Plane.',
            self::TELEPORT_TO_CELESTIAL => 'Use /pct to find and teleport/traverse to the public Celestial Entity.',
            self::FACTION_POINTS => 'Gain 10 faction points per kill starting at level one of the faction.',
            self::GET_COPPER_COINS => 'Enemies in Purgatory drop copper coins relative to their gold (random 5–20 per battle).',
            self::ENTER_PURGATORY_HOUSE => 'Enter the Purgatory Smith house to investigate the Green Growing Light.',
            self::HIDE_CHAT_LOCATION => 'Hides your location from chat so others cannot find and duel you.',
            self::SETTLE_IN_ICE_PLANE => 'Allows you to settle kingdoms on The Ice Plane.',
            self::THE_OLD_CHURCH => 'Gain currency bonuses and uniques at The Old Church on the Ice Plane during the Winter Event.',
            self::MERCENARY_SLOT_BONUS => 'Gain +50% slot-machine currency rewards and +5% Copper Coins in Purgatory Dungeons.',
            self::WALK_ON_DELUSIONAL_MEMORIES_WATER => 'Walk on water on the Delusional Memories plane.',
            self::TWISTED_TREE_BRANCH => 'Access the Twisted Dimensional Gate in Hell to enter Twisted Memories.',
            self::TWISTED_DUNGEONS => 'Access the Dungeons of twisted maidens in Twisted Memories.',
            self::CONTINUE_LEVELING => 'Continue leveling.',
        };
    }
}
