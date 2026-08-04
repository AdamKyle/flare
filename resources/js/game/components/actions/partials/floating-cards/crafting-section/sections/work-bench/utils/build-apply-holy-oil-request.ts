import ApplyHolyOilRequestDefinition from '../api/definitions/apply-holy-oil-request-definition';

export const buildApplyHolyOilRequest = (
  itemId: number | null,
  alchemySlotId: number | null
): ApplyHolyOilRequestDefinition | null => {
  if (itemId === null || alchemySlotId === null) {
    return null;
  }

  return {
    item_id: itemId,
    alchemy_slot_id: alchemySlotId,
  };
};
