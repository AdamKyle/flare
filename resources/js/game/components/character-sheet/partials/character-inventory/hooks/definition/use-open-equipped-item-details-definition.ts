import BaseInventoryItemDefinition from '../../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';

export default interface UseOpenEquippedItemDetailsDefinition {
  openEquippedItemDetails: (equippedItem: BaseInventoryItemDefinition) => void;
}
