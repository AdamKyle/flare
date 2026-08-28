/**
 * Determine whether a raw form input string represents a finite number.
 */
export const isValidNumericString = (value: string): boolean => {
  if (value.trim() === '') {
    return false;
  }

  return Number.isFinite(Number(value));
};
