import EnchantingEventItemDefinition from '../api/definitions/enchanting-event-item-definition';
import EnchantingInventoryItemDefinition from '../api/definitions/enchanting-inventory-item-definition';
import { EnchantingItemSource } from '../enums/enchanting-item-source';

import { DropdownItem } from 'ui/drop-down/types/drop-down-item';

export const buildEnchantingItemOptions = (
  source: EnchantingItemSource,
  regularItems: EnchantingInventoryItemDefinition[],
  eventItems: EnchantingEventItemDefinition[]
): DropdownItem[] => {
  if (source === EnchantingItemSource.EVENT) {
    return eventItems.map((item) => ({
      label: item.item_name,
      value: item.slot_id,
    }));
  }

  return regularItems.map((slot) => ({
    label: slot.item.affix_name ?? slot.item.name,
    value: slot.id,
  }));
};
