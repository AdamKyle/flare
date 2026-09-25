import InventoryCountDefinition from 'game-data/api-data-definitions/character/inventory-counts-definition';

export default interface MarketPurchaseResponseDefinition {
  message: string;
  gold: number;
  inventory_count: InventoryCountDefinition;
}
