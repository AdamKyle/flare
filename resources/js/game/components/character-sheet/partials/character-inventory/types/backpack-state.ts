import BaseInventoryItemDefinition from '../api-definitions/base-inventory-item-definition';

export default interface BackpackState {
  close_backpack: () => void;
  inventory_items: BaseInventoryItemDefinition[];
  quest_items: BaseInventoryItemDefinition[];
}
