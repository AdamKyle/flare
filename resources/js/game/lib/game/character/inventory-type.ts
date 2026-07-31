export default interface InventoryType {
    alchemy_item_count: number;
    alchemy_bag_count: number;
    alchemy_bag_limit: number;
    is_alchemy_bag_full: boolean;
    gem_bag_count: number;
    gem_bag_limit: number;
    is_gem_bag_full: boolean;
    inventory_bag_count: number;
    inventory_count: number;
    inventory_max: number;
    crafted_items_set_count: number;
    crafted_items_set_max: number;
}
