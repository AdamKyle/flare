import isNil from 'lodash/isNil';

/**
 * True when a numeric value is null/undefined or exactly 0.
 *
 * @param value - Number to test (maybe null/undefined)
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

  return value === 0;
};
