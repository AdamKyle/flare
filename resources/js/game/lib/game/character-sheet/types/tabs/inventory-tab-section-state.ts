import InventoryDetails from "../inventory/inventory-details";

export default interface InventoryTabSectionState {
    table: string;

    data: InventoryDetails[] | [];

    show_destroy_all: boolean;

    show_disenchant_all: boolean;

    show_sell_all: boolean;

    success_message: string | null;

    search_string: string;
}