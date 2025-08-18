import isNil from 'lodash/isNil';
import some from 'lodash/some';

import { ItemAdjustments } from '../../../api-definitions/items/item-comparison-details';
import { InventoryItemTypes } from '../../../components/character-sheet/partials/character-inventory/enums/inventory-item-types';
import type {
  FieldDef,
  NumericAdjustmentKey,
} from '../types/item-comparison-types';

/**
 * True when a numeric value is null/undefined or exactly 0.
 *
 * @param value - Number to test (may be null/undefined)
 * @returns Whether the value is nil or exactly zero
 *
 * @example
 * isNilOrZeroValue(null)  // true
 * isNilOrZeroValue(0)     // true
 * isNilOrZeroValue(1)     // false
 */
export const isNilOrZeroValue = (value: number | null | undefined): boolean => {
  if (isNil(value)) {
    return true;
  }

  if (value === 0) {
    return true;
  }

  return false;
};

/**
 * Return the sign prefix for formatting a number.
 * Positive => "+", zero or negative => "-".
 *
 * @param value - Number to sign
 * @returns "+" or "-"
 *
 * @example
 * getSignedPrefix(5)   // "+"
 * getSignedPrefix(-2)  // "-"
 * getSignedPrefix(0)   // "-"
 */
export const getSignedPrefix = (value: number): string => {
  if (value > 0) {
    return '+';
  }

  return '-';
};

/**
 * Format integers with thousands separators (e.g., 1000 → "1,000").
 *
 * @param value - Integer value
 * @returns A formatted string with commas
 *
 * @example
 * formatNumberWithCommas(999)   // "999"
 * formatNumberWithCommas(1234)  // "1,234"
 */
export const formatNumberWithCommas = (value: number): string => {
  if (value < 1000) {
    return value.toString();
  }

  return value.toLocaleString('en-US');
};

/**
 * Format a signed integer using a sign and thousands separators.
 *
 * @param value - Integer to format (sign preserved)
 * @returns Signed, comma-formatted string
 *
 * @example
 * formatSignedInteger(-6834)  // "-6,834"
 * formatSignedInteger(12000)  // "+12,000"
 */
export const formatSignedInteger = (value: number): string => {
  const prefix = getSignedPrefix(value);

  const absoluteValue = Math.abs(value);
  const formatted = formatNumberWithCommas(absoluteValue);

  return `${prefix}${formatted}`;
};

/**
 * Format a signed fractional value as a percentage with two decimals.
 * (1.0 => 100.00%)
 *
 * @param value - Fractional number (1.0 = 100%)
 * @returns Signed percent string
 *
 * @example
 * formatSignedPercent(-1.659)  // "-165.90%"
 * formatSignedPercent(0.25)    // "+25.00%"
 */
export const formatSignedPercent = (value: number): string => {
  const prefix = getSignedPrefix(value);

  const absolutePercent = Math.abs(value * 100).toFixed(2);

  return `${prefix}${absolutePercent}%`;
};

/**
 * Auto format: integers => "+N"/"-N" with commas; non-integers => "+NN.NN%".
 *
 * @param value - Number to format
 * @returns Formatted string
 *
 * @example
 * formatSignedAuto(-6834)   // "-6,834"
 * formatSignedAuto(-1.659)  // "-165.90%"
 */
export const formatSignedAuto = (value: number): string => {
  if (Number.isInteger(value)) {
    return formatSignedInteger(value);
  }

  return formatSignedPercent(value);
};

/**
 * Human word for direction used by screen readers ("increased"/"decreased").
 *
 * @param value - Number to evaluate
 * @returns "increased" when positive, otherwise "decreased"
 *
 * @example
 * getDirectionWord(5)   // "increased"
 * getDirectionWord(-1)  // "decreased"
 * getDirectionWord(0)   // "decreased"
 */
export const getDirectionWord = (value: number): 'increased' | 'decreased' => {
  if (value > 0) {
    return 'increased';
  }

  return 'decreased';
};

/**
 * Build a screen-reader description for a numeric adjustment.
 * - Integers: “… by N”
 * - Non-integers: “… by NN.NN percent”
 * - Zero: “No change in …”
 *
 * @param value - Adjustment value
 * @param label - Human label for the field
 * @returns Human-friendly message for SR users
 *
 * @example
 * getScreenReaderExplanation(-6834, 'Damage Adjustment')
 * // "Damage Adjustment decreased by 6834"
 */
export const getScreenReaderExplanation = (
  value: number,
  label: string
): string => {
  if (value === 0) {
    return `No change in ${label}`;
  }

  const direction = getDirectionWord(value);

  if (Number.isInteger(value)) {
    const integerAmount = Math.abs(value);
    return `${label} ${direction} by ${integerAmount}`;
  }

  const percentAmount = Math.abs(value * 100).toFixed(2);

  return `${label} ${direction} by ${percentAmount} percent`;
};

/**
 * True if any of the provided fields on `adjustments` is non-nil and non-zero.
 *
 * @param adjustments - Item adjustments bag
 * @param fieldDefinitions - Keys to inspect
 * @returns Whether any field is present and non-zero
 *
 * @example
 * hasAnyNonZeroAdjustment(adjustments, TOP_FIELDS) // boolean
 */
export const hasAnyNonZeroAdjustment = (
  adjustments: ItemAdjustments,
  fieldDefinitions: FieldDef[]
): boolean => {
  return some(fieldDefinitions, ({ key }) => {
    const value = adjustments[key] as number | null | undefined;

    return !isNilOrZeroValue(value);
  });
};

/**
 * Slot label, e.g., "left-hand" → "Left hand".
 *
 * @param position - Raw position key
 * @returns Human-friendly label
 *
 * @example
 * getPositionLabel('left-hand') // "Left hand"
 */
export const getPositionLabel = (position?: string): string => {
  if (!position) {
    return 'Unknown slot';
  }

  const label = position.replace('-', ' ');

  return label.charAt(0).toUpperCase() + label.slice(1);
};

/**
 * Heuristic for which item types are two-handed.
 *
 * @param type - Raw item type
 * @returns Whether the type is considered two-handed
 *
 * @example
 * isTwoHandedType('stave') // true
 */
export const isTwoHandedType = (type?: InventoryItemTypes): boolean => {
  if (!type) {
    return false;
  }

  const twoHanded = [
    InventoryItemTypes.STAVE,
    InventoryItemTypes.BOW,
    InventoryItemTypes.HAMMER,
  ];

  return twoHanded.includes(type.toLowerCase() as InventoryItemTypes);
};

/**
 * Keys in a field list that are present and exactly zero (not null/undefined).
 * Useful to force-render zeros in Advanced mode.
 *
 * @param adjustments - Item adjustments
 * @param fields - Candidate fields to check
 * @returns Array of keys that are exactly zero
 *
 * @example
 * const zeroKeys = getZeroPresentKeys(adjustments, AFFIX_ADJUSTMENT_FIELDS);
 */
export const getZeroPresentKeys = (
  adjustments: ItemAdjustments,
  fields: { key: NumericAdjustmentKey }[]
): NumericAdjustmentKey[] => {
  const zeroKeys: NumericAdjustmentKey[] = [];

  for (const { key } of fields) {
    const value = adjustments[key] as number | null | undefined;

    if (!isNil(value) && Number(value) === 0) {
      zeroKeys.push(key);
    }
  }

  return zeroKeys;
};
