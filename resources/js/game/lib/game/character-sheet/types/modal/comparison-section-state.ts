import InventoryComparisonAdjustment from "./inventory-comparison-adjustment";

export default interface ComparisonSectionState {
    show_equip_modal: boolean;

    show_move_modal: boolean;

    show_sell_modal: boolean;

    show_list_item_modal: boolean;

    show_item_details: boolean;

    item_to_sell: InventoryComparisonAdjustment|null;

    item_to_show: InventoryComparisonAdjustment|null;

    error_message: string|null;
}
