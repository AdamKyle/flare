import BaseInventoryItemDefinition from '../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';

export default interface BackpackProps {
  close_backpack: () => void;
  inventory_items: BaseInventoryItemDefinition[];
  quest_items: BaseInventoryItemDefinition[];
}
