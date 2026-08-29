export enum ItemSpecialtyType {
  HELL_FORGED = 'Hell Forged',
  PURGATORY_CHAINS = 'Purgatory Chains',
  PIRATE_LORD_LEATHER = 'Pirate Lord Leather',
  CORRUPTED_ICE = 'Corrupted Ice',
  DELUSIONAL_SILVER = 'Delusional Silver',
  TWISTED_EARTH = 'Twisted Earth',
  FAITHLESS_PLATE = 'Faithless Plate',
  LABYRINTH_CLOTH = 'Labyrinth Cloth',
}

export const ITEM_SPECIALTY_TYPE_LABELS: Record<ItemSpecialtyType, string> = {
  [ItemSpecialtyType.HELL_FORGED]: 'Hell Forged',
  [ItemSpecialtyType.PURGATORY_CHAINS]: 'Purgatory Chains',
  [ItemSpecialtyType.PIRATE_LORD_LEATHER]: 'Pirate Lord Leather',
  [ItemSpecialtyType.CORRUPTED_ICE]: 'Corrupted Ice',
  [ItemSpecialtyType.DELUSIONAL_SILVER]: 'Delusional Silver',
  [ItemSpecialtyType.TWISTED_EARTH]: 'Twisted Earth',
  [ItemSpecialtyType.FAITHLESS_PLATE]: 'Faithless Plate',
  [ItemSpecialtyType.LABYRINTH_CLOTH]: 'Labyrinth Cloth',
};

export const ITEM_SPECIALTY_TYPE_VALUES: ItemSpecialtyType[] = [
  ItemSpecialtyType.HELL_FORGED,
  ItemSpecialtyType.PURGATORY_CHAINS,
  ItemSpecialtyType.PIRATE_LORD_LEATHER,
  ItemSpecialtyType.CORRUPTED_ICE,
  ItemSpecialtyType.DELUSIONAL_SILVER,
  ItemSpecialtyType.TWISTED_EARTH,
  ItemSpecialtyType.FAITHLESS_PLATE,
  ItemSpecialtyType.LABYRINTH_CLOTH,
];

/**
 * Narrow a Dropdown's generic `string | number` selection value down to a
 * known Item specialty type, without a forced type assertion at each call site.
 */
export const isItemSpecialtyType = (
  value: string | number
): value is ItemSpecialtyType => {
  if (typeof value !== 'string') {
    return false;
  }

  return ITEM_SPECIALTY_TYPE_VALUES.some(
    (specialtyType) => specialtyType === value
  );
};
