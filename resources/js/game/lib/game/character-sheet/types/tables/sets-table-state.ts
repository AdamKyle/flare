import InventoryDetails from "../inventory/inventory-details";

export default interface SetsTableState {
    data: InventoryDetails[] | [];

    drop_down_labels: string[];

    selected_set: string | null;

    selected_set_index: number | null;

    success_message: string | null;

    show_rename_set: boolean;

    loading: boolean;

    search_string: string;

    item_id: number | null;

    view_item: boolean;

    loading_label: string | null;

    show_loading_label: boolean;
}
