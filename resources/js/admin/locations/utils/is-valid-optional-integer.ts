/**
 * Determine whether a wizard text-input value is empty or a non-negative integer.
 */
export const isValidOptionalInteger = (value: string): boolean => {
  if (value.trim() === '') {
    return true;
  }

  const parsedValue = Number(value);

  return (
    Number.isFinite(parsedValue) &&
    Number.isInteger(parsedValue) &&
    parsedValue >= 0
  );
};
