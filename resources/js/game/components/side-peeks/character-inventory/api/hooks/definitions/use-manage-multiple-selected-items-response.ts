import InventoryCountDefinition from 'game-data/api-data-definitions/character/inventory-counts-definition';

export default interface UseManageMultipleSelectedItemsResponse {
  message: string;
  inventory_count: InventoryCountDefinition;
}
