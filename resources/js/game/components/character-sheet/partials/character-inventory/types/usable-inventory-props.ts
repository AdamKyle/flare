import BaseInventoryItemDefinition from '../api-definitions/base-inventory-item-definition';

export default interface UsableInventoryProps {
  close_usable_Section: () => void;
  usable_items: BaseInventoryItemDefinition[];
}
