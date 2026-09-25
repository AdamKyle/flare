import {
  armourPositions,
  InventoryItemTypes,
  weaponTypes,
} from '../../character-sheet/partials/character-inventory/enums/inventory-item-types';

const EQUIPPABLE_MARKET_TYPES = new Set<string>([
  ...weaponTypes,
  ...armourPositions,
  InventoryItemTypes.RING,
  InventoryItemTypes.SPELL_HEALING,
  InventoryItemTypes.SPELL_DAMAGE,
  InventoryItemTypes.TRINKET,
  InventoryItemTypes.ARTIFACT,
]);

export const isEquippableMarketType = (type: string): boolean =>
  EQUIPPABLE_MARKET_TYPES.has(type);
