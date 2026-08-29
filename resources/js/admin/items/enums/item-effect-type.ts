export enum ItemEffectType {
  WALK_ON_WATER = 'walk-on-water',
  WALK_ON_DEATH_WATER = 'walk-on-death-water',
  LABYRINTH = 'labyrinth',
  DUNGEON = 'dungeon',
  SHADOW_PLANE = 'shadow-plane',
  HELL = 'hell',
  PURGATORY = 'purgatory',
  TELEPORT_TO_CELESTIAL = 'teleport-to-celestial',
  AFFIXES_IRRESISTIBLE = 'affixes-irresistible',
  CONTINUE_LEVELING = 'continue-leveling',
  GOLD_DUST_RUSH = 'gold-dust-rush',
  MASS_EMBEZZLE = 'mass-embezzle',
  WALK_ON_MAGMA = 'walk-on-magma',
  QUEEN_OF_HEARTS = 'speak-to-queen-of-hearts',
  FACTION_POINTS = 'effects-faction-points',
  GET_COPPER_COINS = 'get-copper-coins',
  ENTER_PURGATORY_HOUSE = 'enter-purgatory-house',
  HIDE_CHAT_LOCATION = 'hide-chat-location',
  WALK_ON_ICE = 'walk-on-ice',
  SETTLE_IN_ICE_PLANE = 'settle-on-the-ice-plane',
  THE_OLD_CHURCH = 'the-old-church',
  MERCENARY_SLOT_BONUS = 'mercenary-slot-bonus',
  WALK_ON_DELUSIONAL_MEMORIES_WATER = 'walk-on-delusional-memories-water',
  TWISTED_TREE_BRANCH = 'access-twisted-memories',
  TWISTED_DUNGEONS = 'twisted-dungeons',
  DELVE = 'delve',
  DELVE_PACK_CHOICE = 'delve-pack-choice',
}

export const ITEM_EFFECT_TYPE_LABELS: Record<ItemEffectType, string> = {
  [ItemEffectType.WALK_ON_WATER]: 'Walk on water (Surface and Labyrinth)',
  [ItemEffectType.WALK_ON_DEATH_WATER]:
    'Walk on Death Water in Dungeons Plane.',
  [ItemEffectType.LABYRINTH]:
    'Use Traverse (beside movement map-actions) to traverse to Labyrinth plane',
  [ItemEffectType.DUNGEON]:
    'Use Traverse (beside movement map-actions) to traverse to Dungeons plane',
  [ItemEffectType.SHADOW_PLANE]:
    'Use Traverse (beside movement map-actions) to traverse to Shadow Plane',
  [ItemEffectType.HELL]:
    'Use Traverse (beside movement map-actions) to traverse to Hell plane',
  [ItemEffectType.PURGATORY]:
    'Use Traverse (beside movement map-actions) to traverse to Purgatory plane (only while in Hell at Tear in the Fabric of Time: X/Y 208/64)',
  [ItemEffectType.TELEPORT_TO_CELESTIAL]:
    'Use /pct to find and teleport/traverse to the public Celestial Entity.',
  [ItemEffectType.AFFIXES_IRRESISTIBLE]:
    'Makes affix damage irresistible except in Hell and Purgatory.',
  [ItemEffectType.CONTINUE_LEVELING]: 'Continue leveling.',
  [ItemEffectType.GOLD_DUST_RUSH]:
    'Provides a small chance to get a gold dust rush when disenchanting.',
  [ItemEffectType.MASS_EMBEZZLE]:
    'Lets you mass embezzle from all kingdoms on the plane. Go to Kingdoms → select a kingdom → Mass Embezzle (not cross-plane).',
  [ItemEffectType.WALK_ON_MAGMA]: 'Lets you walk on Magma in Hell.',
  [ItemEffectType.QUEEN_OF_HEARTS]:
    'Lets a character approach and speak to the Queen of Hearts in Hell.',
  [ItemEffectType.FACTION_POINTS]:
    'Gain 10 faction points per kill starting at level one of the faction.',
  [ItemEffectType.GET_COPPER_COINS]:
    'Enemies in Purgatory drop copper coins relative to their gold (random 5–20 per battle).',
  [ItemEffectType.ENTER_PURGATORY_HOUSE]:
    'Enter the Purgatory Smith house to investigate the Green Growing Light.',
  [ItemEffectType.HIDE_CHAT_LOCATION]:
    'Hides your location from chat so others cannot find and duel you.',
  [ItemEffectType.WALK_ON_ICE]: 'Walk on Ice (The Ice Plane)',
  [ItemEffectType.SETTLE_IN_ICE_PLANE]:
    'Allows you to settle kingdoms on The Ice Plane.',
  [ItemEffectType.THE_OLD_CHURCH]:
    'Gain currency bonuses and uniques at The Old Church on the Ice Plane during the Winter Event.',
  [ItemEffectType.MERCENARY_SLOT_BONUS]:
    'Gain +50% slot-machine currency rewards and +5% Copper Coins in Purgatory Dungeons.',
  [ItemEffectType.WALK_ON_DELUSIONAL_MEMORIES_WATER]:
    'Walk on water on the Delusional Memories plane.',
  [ItemEffectType.TWISTED_TREE_BRANCH]:
    'Access the Twisted Dimensional Gate in Hell to enter Twisted Memories.',
  [ItemEffectType.TWISTED_DUNGEONS]:
    'Access the Dungeons of twisted maidens in Twisted Memories.',
  [ItemEffectType.DELVE]: 'Access Delve locations.',
  [ItemEffectType.DELVE_PACK_CHOICE]: 'Choose a Delve reward pack.',
};

export const ITEM_EFFECT_TYPE_VALUES: ItemEffectType[] = [
  ItemEffectType.WALK_ON_WATER,
  ItemEffectType.WALK_ON_DEATH_WATER,
  ItemEffectType.LABYRINTH,
  ItemEffectType.DUNGEON,
  ItemEffectType.SHADOW_PLANE,
  ItemEffectType.HELL,
  ItemEffectType.PURGATORY,
  ItemEffectType.TELEPORT_TO_CELESTIAL,
  ItemEffectType.AFFIXES_IRRESISTIBLE,
  ItemEffectType.CONTINUE_LEVELING,
  ItemEffectType.GOLD_DUST_RUSH,
  ItemEffectType.MASS_EMBEZZLE,
  ItemEffectType.WALK_ON_MAGMA,
  ItemEffectType.QUEEN_OF_HEARTS,
  ItemEffectType.FACTION_POINTS,
  ItemEffectType.GET_COPPER_COINS,
  ItemEffectType.ENTER_PURGATORY_HOUSE,
  ItemEffectType.HIDE_CHAT_LOCATION,
  ItemEffectType.WALK_ON_ICE,
  ItemEffectType.SETTLE_IN_ICE_PLANE,
  ItemEffectType.THE_OLD_CHURCH,
  ItemEffectType.MERCENARY_SLOT_BONUS,
  ItemEffectType.WALK_ON_DELUSIONAL_MEMORIES_WATER,
  ItemEffectType.TWISTED_TREE_BRANCH,
  ItemEffectType.TWISTED_DUNGEONS,
  ItemEffectType.DELVE,
  ItemEffectType.DELVE_PACK_CHOICE,
];

/**
 * Narrow a Dropdown's generic `string | number` selection value down to a
 * known Item effect type, without a forced type assertion at each call site.
 */
export const isItemEffectType = (
  value: string | number
): value is ItemEffectType => {
  if (typeof value !== 'string') {
    return false;
  }

  return ITEM_EFFECT_TYPE_VALUES.some((effectType) => effectType === value);
};
