import { ITEM_TYPE_LABELS } from '../../../reusable-components/item/constants/item-type-labels';
import { InventoryItemTypes } from '../../character-sheet/partials/character-inventory/enums/inventory-item-types';

const MARKET_FILTER_ITEM_TYPES: InventoryItemTypes[] = [
  InventoryItemTypes.SWORD,
  InventoryItemTypes.DAGGER,
  InventoryItemTypes.CLAW,
  InventoryItemTypes.CENSER,
  InventoryItemTypes.WAND,
  InventoryItemTypes.STAVE,
  InventoryItemTypes.BOW,
  InventoryItemTypes.GUN,
  InventoryItemTypes.FAN,
  InventoryItemTypes.MACE,
  InventoryItemTypes.HAMMER,
  InventoryItemTypes.SCRATCH_AWL,
  InventoryItemTypes.SHIELD,
  InventoryItemTypes.BODY,
  InventoryItemTypes.HELMET,
  InventoryItemTypes.GLOVES,
  InventoryItemTypes.SLEEVES,
  InventoryItemTypes.LEGGINGS,
  InventoryItemTypes.FEET,
  InventoryItemTypes.RING,
  InventoryItemTypes.SPELL_HEALING,
  InventoryItemTypes.SPELL_DAMAGE,
  InventoryItemTypes.TRINKET,
  InventoryItemTypes.ARTIFACT,
];

export const MARKET_ITEM_TYPE_LABELS: Record<string, string> = {
  ...Object.fromEntries(
    MARKET_FILTER_ITEM_TYPES.map((type) => [type, ITEM_TYPE_LABELS[type]])
  ),
  alchemy: 'Alchemy',
};

export const resolveMarketItemTypeLabel = (type: string): string =>
  MARKET_ITEM_TYPE_LABELS[type] ?? type;
