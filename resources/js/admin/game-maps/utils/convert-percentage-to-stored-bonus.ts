/**
 * Convert a Game Map bonus percentage value shown in the form (e.g. 10) into the
 * stored decimal value sent to the API (e.g. 0.1).
 */
export const convertPercentageToStoredBonus = (value: number): number =>
  value / 100;
