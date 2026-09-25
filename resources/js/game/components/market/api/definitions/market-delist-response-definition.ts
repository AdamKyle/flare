import InventoryCountDefinition from 'game-data/api-data-definitions/character/inventory-counts-definition';

export default interface MarketDelistResponseDefinition {
  message: string;
  inventory_count: InventoryCountDefinition;
}
