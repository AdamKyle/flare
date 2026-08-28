/**
 * Convert a stored decimal Game Map bonus value (e.g. 0.1) into the percentage value
 * shown in the form (e.g. 10).
 */
export const convertStoredBonusToPercentage = (value: number): number =>
  value * 100;
