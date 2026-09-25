import some from 'lodash/some';

import { resolveItemPositionLabel } from './resolve-item-labels';
import { ItemAdjustments } from '../../../api-definitions/items/item-comparison-details';
import { InventoryItemTypes } from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import type { FieldDef } from '../types/item-comparison-types';

import { formatNumberWithCommas } from 'game-utils/format-number';
import { isNilOrZeroValue } from 'game-utils/general-util';

export const getSignedPrefix = (value: number): string => {
  if (value > 0) {
    return '+';
  }

  return '-';
};

export const formatSignedInteger = (value: number): string => {
  const prefix = getSignedPrefix(value);

  return `${prefix}${formatNumberWithCommas(Math.abs(value))}`;
};

export const formatSignedPercent = (value: number): string => {
  const prefix = getSignedPrefix(value);

  return `${prefix}${Math.abs(value * 100).toFixed(2)}%`;
};

export const formatSignedAuto = (value: number): string => {
  if (Number.isInteger(value)) {
    return formatSignedInteger(value);
  }

  return formatSignedPercent(value);
};

export const getDirectionWord = (value: number): 'increased' | 'decreased' => {
  if (value > 0) {
    return 'increased';
  }

  return 'decreased';
};

export const getScreenReaderExplanation = (
  value: number,
  label: string
): string => {
  if (value === 0) {
    return `No change in ${label}`;
  }

  const direction = getDirectionWord(value);

  if (Number.isInteger(value)) {
    return `${label} ${direction} by ${Math.abs(value)}`;
  }

  return `${label} ${direction} by ${Math.abs(value * 100).toFixed(2)} percent`;
};

export const hasAnyNonZeroAdjustment = (
  adjustments: ItemAdjustments,
  fieldDefinitions: FieldDef[]
): boolean =>
  some(fieldDefinitions, ({ key }) => !isNilOrZeroValue(adjustments[key]));

export const getPositionLabel = (position?: string): string => {
  if (!position) {
    return 'Unknown slot';
  }

  return resolveItemPositionLabel(position);
};

const TWO_HANDED_ITEM_TYPES: InventoryItemTypes[] = [
  InventoryItemTypes.STAVE,
  InventoryItemTypes.BOW,
  InventoryItemTypes.HAMMER,
];

export const isTwoHandedType = (type?: InventoryItemTypes): boolean => {
  if (!type) {
    return false;
  }

  return TWO_HANDED_ITEM_TYPES.includes(type);
};
