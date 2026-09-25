import { InventoryItemTypes } from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import { ITEM_POSITION_LABELS } from '../constants/item-position-labels';
import { ITEM_TYPE_LABELS } from '../constants/item-type-labels';
import { ItemPositions } from '../enums/item-positions';

const isInventoryItemType = (value: string): value is InventoryItemTypes =>
  Object.values<string>(InventoryItemTypes).includes(value);

const isItemPosition = (value: string): value is ItemPositions =>
  Object.values<string>(ItemPositions).includes(value);

export const resolveItemTypeLabel = (type: string): string => {
  if (!isInventoryItemType(type)) {
    return type;
  }

  return ITEM_TYPE_LABELS[type];
};

export const resolveItemPositionLabel = (position: string): string => {
  if (!isItemPosition(position)) {
    return position;
  }

  return ITEM_POSITION_LABELS[position];
};
