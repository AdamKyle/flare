import BaseInventoryItemDefinition from '../../../../side-peeks/character-inventory/api-definitions/base-inventory-item-definition';

export default interface EquippedSlotsProps {
  equipped_items: BaseInventoryItemDefinition[];
  on_open_item_details: (equippedItem: BaseInventoryItemDefinition) => void;
}
