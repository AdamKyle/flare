/**
 * The Delve job falls back to a pack of one when no pack size is chosen, so
 * one is sent unless the Character may choose (and has entered) a whole
 * pack size of at least one.
 */
export const resolveDelvePackSize = (
  canSetPackSize: boolean,
  packSizeInput: string
): number | null => {
  if (!canSetPackSize) {
    return 1;
  }

  const parsedPackSize = Number(packSizeInput);

  if (!Number.isFinite(parsedPackSize) || !Number.isInteger(parsedPackSize)) {
    return null;
  }

  if (parsedPackSize < 1) {
    return null;
  }

  return parsedPackSize;
};
