export enum ItemProfile {
  ALL = 'all',
  WEAPONS = 'weapons',
  ARMOUR = 'armour',
  DAMAGE_SPELLS = 'damage-spells',
  HEALING_SPELLS = 'healing-spells',
  RINGS = 'rings',
  TRINKETS = 'trinkets',
  ARTIFACTS = 'artifacts',
  QUEST_ITEMS = 'quest-items',
  ALCHEMY = 'alchemy',
  SPECIALTY = 'specialty',
}

export const ITEM_PROFILE_LABELS: Record<ItemProfile, string> = {
  [ItemProfile.ALL]: 'All Items',
  [ItemProfile.WEAPONS]: 'Weapons',
  [ItemProfile.ARMOUR]: 'Armour',
  [ItemProfile.DAMAGE_SPELLS]: 'Damage Spells',
  [ItemProfile.HEALING_SPELLS]: 'Healing Spells',
  [ItemProfile.RINGS]: 'Rings',
  [ItemProfile.TRINKETS]: 'Trinkets',
  [ItemProfile.ARTIFACTS]: 'Artifacts',
  [ItemProfile.QUEST_ITEMS]: 'Quest Items',
  [ItemProfile.ALCHEMY]: 'Alchemy',
  [ItemProfile.SPECIALTY]: 'Specialty',
};

export const ITEM_PROFILE_DEFAULT_SORT_KEY: Record<ItemProfile, string> = {
  [ItemProfile.ALL]: 'name',
  [ItemProfile.WEAPONS]: 'name',
  [ItemProfile.ARMOUR]: 'name',
  [ItemProfile.DAMAGE_SPELLS]: 'name',
  [ItemProfile.HEALING_SPELLS]: 'name',
  [ItemProfile.RINGS]: 'name',
  [ItemProfile.TRINKETS]: 'name',
  [ItemProfile.ARTIFACTS]: 'name',
  [ItemProfile.QUEST_ITEMS]: 'name',
  [ItemProfile.ALCHEMY]: 'name',
  [ItemProfile.SPECIALTY]: 'name',
};

export const ITEM_PROFILE_VALUES: ItemProfile[] = [
  ItemProfile.ALL,
  ItemProfile.WEAPONS,
  ItemProfile.ARMOUR,
  ItemProfile.DAMAGE_SPELLS,
  ItemProfile.HEALING_SPELLS,
  ItemProfile.RINGS,
  ItemProfile.TRINKETS,
  ItemProfile.ARTIFACTS,
  ItemProfile.QUEST_ITEMS,
  ItemProfile.ALCHEMY,
  ItemProfile.SPECIALTY,
];

export const isItemProfile = (value: string | number): value is ItemProfile => {
  if (typeof value !== 'string') {
    return false;
  }

  return ITEM_PROFILE_VALUES.some((profile) => profile === value);
};
