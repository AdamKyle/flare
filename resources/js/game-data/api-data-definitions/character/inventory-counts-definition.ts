interface InventoryCountDataDefinition {
  inventory_max: number;
  inventory_count: number;
  inventory_bag_count: number;
  alchemy_item_count: number;
  alchemy_bag_count: number;
  alchemy_bag_limit: number;
  is_alchemy_bag_full: boolean;
  gem_bag_count: number;
  gem_bag_limit: number;
  is_gem_bag_full: boolean;
}

export default interface InventoryCountsDefinition {
  data: InventoryCountDataDefinition;
}
