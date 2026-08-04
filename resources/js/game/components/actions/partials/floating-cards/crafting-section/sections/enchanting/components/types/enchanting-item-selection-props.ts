import EnchantingEventItemDefinition from '../../api/definitions/enchanting-event-item-definition';
import EnchantingInventoryItemDefinition from '../../api/definitions/enchanting-inventory-item-definition';
import { EnchantingItemSource } from '../../enums/enchanting-item-source';
export default interface EnchantingItemSelectionProps {
  regularItems: EnchantingInventoryItemDefinition[];
  eventItems: EnchantingEventItemDefinition[];
  source: EnchantingItemSource;
  selectedSlotId: number | null;
  onSelect: (id: number) => void;
}
