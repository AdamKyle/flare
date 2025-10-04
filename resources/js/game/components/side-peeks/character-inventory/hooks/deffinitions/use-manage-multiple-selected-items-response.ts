import InventoryCountDefinition from 'game-data/api-data-definitions/character/inventory-count-definition';

export default interface UseManageMultipleSelectedItemsResponse {
  message: string;
  inventory_count: InventoryCountDefinition;
}
