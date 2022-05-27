import InventoryComparisonAdjustment from "./inventory-comparison-adjustment";

export default interface ComparisonSectionState {
    show_equip_modal: boolean;

    show_move_modal: boolean;

    show_sell_modal: boolean;

    show_list_item_modal: boolean;

    item_to_sell: InventoryComparisonAdjustment|null;
}
