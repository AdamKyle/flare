import InventoryDetails from "../inventory/inventory-details";

export default interface SetsTableState {
    data: InventoryDetails[] | [];

    drop_down_labels: string[];

    selected_set: string | null;

    loading: boolean;
}
