export enum ItemDefaultPosition {
  BODY = 'body',
  LEGGINGS = 'leggings',
  FEET = 'feet',
  SLEEVES = 'sleeves',
  HELMET = 'helmet',
  GLOVES = 'gloves',
}

export const ITEM_DEFAULT_POSITION_LABELS: Record<ItemDefaultPosition, string> =
  {
    [ItemDefaultPosition.BODY]: 'Body',
    [ItemDefaultPosition.LEGGINGS]: 'Leggings',
    [ItemDefaultPosition.FEET]: 'Feet',
    [ItemDefaultPosition.SLEEVES]: 'Sleeves',
    [ItemDefaultPosition.HELMET]: 'Helmet',
    [ItemDefaultPosition.GLOVES]: 'Gloves',
  };

export const ITEM_DEFAULT_POSITION_VALUES: ItemDefaultPosition[] = [
  ItemDefaultPosition.BODY,
  ItemDefaultPosition.LEGGINGS,
  ItemDefaultPosition.FEET,
  ItemDefaultPosition.SLEEVES,
  ItemDefaultPosition.HELMET,
  ItemDefaultPosition.GLOVES,
];

/**
 * Narrow a Dropdown's generic `string | number` selection value down to a
 * known Item default position, without a forced type assertion at each call site.
 */
export const isItemDefaultPosition = (
  value: string | number
): value is ItemDefaultPosition => {
  if (typeof value !== 'string') {
    return false;
  }

  return ITEM_DEFAULT_POSITION_VALUES.some(
    (defaultPosition) => defaultPosition === value
  );
};
