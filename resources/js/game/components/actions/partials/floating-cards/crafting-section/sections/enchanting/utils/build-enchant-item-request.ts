import EnchantItemRequestDefinition from '../api/definitions/enchant-item-request-definition';
import { EnchantingItemSource } from '../enums/enchanting-item-source';

export const buildEnchantItemRequest = (
  slotId: number | null,
  affixIds: number[],
  source: EnchantingItemSource | null
): EnchantItemRequestDefinition | null => {
  if (slotId === null || source === null || affixIds.length === 0) {
    return null;
  }

  return {
    slot_id: slotId,
    affix_ids: affixIds,
    enchant_for_event: source === EnchantingItemSource.EVENT,
  };
};
