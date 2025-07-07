import { startCase } from 'lodash';

import { InventoryItemTypes } from '../../character-sheet/partials/character-inventory/enums/inventory-item-types';

const shopTypes = [
  InventoryItemTypes.HAMMER,
  InventoryItemTypes.BOW,
  InventoryItemTypes.FAN,
  InventoryItemTypes.GUN,
  InventoryItemTypes.MACE,
  InventoryItemTypes.WAND,
  InventoryItemTypes.CLAW,
  InventoryItemTypes.SWORD,
  InventoryItemTypes.STAVE,
  InventoryItemTypes.CENSOR,
  InventoryItemTypes.SCRATCH_AWL,
  InventoryItemTypes.RING,
  InventoryItemTypes.SPELL_HEALING,
  InventoryItemTypes.SPELL_DAMAGE,
  InventoryItemTypes.SHIELD,
  InventoryItemTypes.BODY,
  InventoryItemTypes.HELMET,
  InventoryItemTypes.GLOVES,
  InventoryItemTypes.LEGGINGS,
  InventoryItemTypes.FEET,
];

type SelectionItem = {
  label: string;
  value: string;
};

export const buildShopItemTypeSelection = (): SelectionItem[] =>
  shopTypes.map((type) => ({
    label: startCase(type),
    value: type,
  }));
