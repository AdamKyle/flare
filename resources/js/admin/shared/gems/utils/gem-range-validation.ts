const GEM_RANGE_PATTERN = /^\d+(\.\d+)?-\d+(\.\d+)?$/;
const ABSENT_RANGE_PATTERN = /^0+(\.0+)?(-0+(\.0+)?)?$/;

export const GEM_RANGE_FORMAT_ERROR =
  'The range must contain two nonnegative numeric values separated by a hyphen.';

export const GEM_RANGE_ORDER_ERROR =
  'The range minimum must be less than or equal to the maximum.';

/**
 * A blank value, or an all-zero value/range, represents "no effect configured".
 */
export const isAbsentGemRangeValue = (value: string): boolean => {
  const trimmed = value.trim();

  return trimmed === '' || ABSENT_RANGE_PATTERN.test(trimmed);
};

/**
 * Returns a validation error message, or null when the value is a valid
 * range or an absent (blank/all-zero) value.
 */
export const validateGemRangeValue = (value: string): string | null => {
  const trimmed = value.trim();

  if (isAbsentGemRangeValue(trimmed)) {
    return null;
  }

  if (!GEM_RANGE_PATTERN.test(trimmed)) {
    return GEM_RANGE_FORMAT_ERROR;
  }

  const [min, max] = trimmed.split('-').map(Number);

  if (min > max) {
    return GEM_RANGE_ORDER_ERROR;
  }

  return null;
};

/**
 * Normalizes a form field's raw text into the value the request payload
 * should carry: null when absent (blank or all-zero), the trimmed value
 * otherwise.
 */
export const normalizeGemRangeValue = (value: string): string | null => {
  const trimmed = value.trim();

  return isAbsentGemRangeValue(trimmed) ? null : trimmed;
};
