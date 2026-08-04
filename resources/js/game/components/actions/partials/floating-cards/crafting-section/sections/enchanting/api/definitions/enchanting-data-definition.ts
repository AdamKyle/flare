import EnchantingAffixDefinition from './enchanting-affix-definition';
import EnchantingEventItemDefinition from './enchanting-event-item-definition';
import EnchantingInventoryItemDefinition from './enchanting-inventory-item-definition';
export default interface EnchantingDataDefinition {
  affixes: EnchantingAffixDefinition[];
  character_inventory: EnchantingInventoryItemDefinition[];
  show_enchanting_for_event: boolean;
  items_for_event: EnchantingEventItemDefinition[];
}
