import InventoryDetails from "../inventory/inventory-details";
import UsableItemsDetails from "../inventory/usable-items-details";

export default interface InventoryTabSectionState {
    table: string;

    data: InventoryDetails[] | [];

    usable_items: UsableItemsDetails[] | [];

    show_destroy_all: boolean;

    show_destroy_all_alchemy: boolean;

    show_disenchant_all: boolean;

    show_sell_all: boolean;

    show_use_many: boolean;

    success_message: string | null;

    search_string: string;
}
