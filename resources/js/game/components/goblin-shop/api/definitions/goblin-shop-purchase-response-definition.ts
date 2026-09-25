import InventoryCountDefinition from 'game-data/api-data-definitions/character/inventory-counts-definition';

export default interface GoblinShopPurchaseResponseDefinition {
  message: string;
  character_gold_bars: number;
  inventory_count: InventoryCountDefinition;
}
