import InventoryCountDefinition from 'game-data/api-data-definitions/character/inventory-counts-definition';

export default interface UsePurchaseAndReplaceApiResponseDefinition {
  message: string;
  inventory_count: InventoryCountDefinition;
  gold: number;
}
