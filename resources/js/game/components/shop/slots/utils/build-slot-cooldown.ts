import SlotCooldown from '../types/slot-cooldown';

export const buildSlotCooldown = (timeoutFor: number): SlotCooldown | null => {
  if (timeoutFor <= 0) {
    return null;
  }

  return {
    ends_at: new Date(Date.now() + timeoutFor * 1000).toISOString(),
    length: timeoutFor,
  };
};
