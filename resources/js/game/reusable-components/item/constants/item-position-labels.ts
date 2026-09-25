import { ItemPositions } from '../enums/item-positions';

export const ITEM_POSITION_LABELS: Record<ItemPositions, string> = {
  [ItemPositions.LEFT_HAND]: 'Left Hand',
  [ItemPositions.RIGHT_HAND]: 'Right Hand',
  [ItemPositions.SPELL_ONE]: 'Spell One',
  [ItemPositions.SPELL_TWO]: 'Spell Two',
  [ItemPositions.RING_ONE]: 'Ring One',
  [ItemPositions.RING_TWO]: 'Ring Two',
  [ItemPositions.TRINKET]: 'Trinket',
  [ItemPositions.BODY]: 'Body',
  [ItemPositions.HELMET]: 'Helmet',
  [ItemPositions.LEGGINGS]: 'Leggings',
  [ItemPositions.SLEEVES]: 'Sleeves',
  [ItemPositions.GLOVES]: 'Gloves',
  [ItemPositions.FEET]: 'Feet',
  [ItemPositions.ARTIFACT]: 'Artifact',
};
