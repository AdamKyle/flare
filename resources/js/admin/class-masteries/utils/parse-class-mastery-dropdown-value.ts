/**
 * Parse a Dropdown item's `value` (string | number) into a numeric id, or
 * null when the value cannot be parsed as a finite number.
 */
export const parseNumberOption = (value: string | number): number | null => {
  const parsed = Number(value);

  return Number.isFinite(parsed) ? parsed : null;
};
