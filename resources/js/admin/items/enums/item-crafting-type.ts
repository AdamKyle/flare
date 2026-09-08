export enum ItemCraftingType {
  WEAPON = 'weapon',
  ARMOUR = 'armour',
  RING = 'ring',
  SPELL = 'spell',
  ARTIFACT = 'artifact',
  ALCHEMY = 'alchemy',
}

export const ITEM_CRAFTING_TYPE_LABELS: Record<ItemCraftingType, string> = {
  [ItemCraftingType.WEAPON]: 'Weapon',
  [ItemCraftingType.ARMOUR]: 'Armour',
  [ItemCraftingType.RING]: 'Ring',
  [ItemCraftingType.SPELL]: 'Spell',
  [ItemCraftingType.ARTIFACT]: 'Artifact',
  [ItemCraftingType.ALCHEMY]: 'Alchemy',
};

export const ITEM_CRAFTING_TYPE_VALUES: ItemCraftingType[] = [
  ItemCraftingType.WEAPON,
  ItemCraftingType.ARMOUR,
  ItemCraftingType.RING,
  ItemCraftingType.SPELL,
  ItemCraftingType.ARTIFACT,
  ItemCraftingType.ALCHEMY,
];

export const isItemCraftingType = (
  value: string | number
): value is ItemCraftingType => {
  if (typeof value !== 'string') {
    return false;
  }

  return ITEM_CRAFTING_TYPE_VALUES.some(
    (craftingType) => craftingType === value
  );
};
