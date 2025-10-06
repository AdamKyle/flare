import InventoryCountDefinition from 'game-data/api-data-definitions/character/inventory-count-definition';

export default interface UsePurchaseAndReplaceApiResponseDefinition {
  message: string;
  inventory_count: InventoryCountDefinition;
  gold: number;
}
