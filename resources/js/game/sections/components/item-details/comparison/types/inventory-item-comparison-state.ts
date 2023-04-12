import InventoryComparisonAdjustment from "./inventory-comparison-adjustment";
import ComparisonDetails from "../../../../../lib/game/character-sheet/types/inventory/comparison-details";

export default interface InventoryItemComparisonState {

    comparison_details: ComparisonDetails | null;

    show_equip_modal: boolean;

    show_move_modal: boolean;

    action_loading: boolean;

    show_sell_modal: boolean;

    show_list_item_modal: boolean;


    item_to_sell: InventoryComparisonAdjustment | null;

    loading: boolean;
}
