const SLOT_REEL_COUNT = 3;

export const isValidSlotRollSet = (
  rolls: unknown,
  symbolCount: number
): rolls is number[] => {
  if (!Array.isArray(rolls) || rolls.length !== SLOT_REEL_COUNT) {
    return false;
  }

  return rolls.every(
    (roll) =>
      typeof roll === 'number' &&
      Number.isInteger(roll) &&
      roll >= 0 &&
      roll < symbolCount
  );
};
