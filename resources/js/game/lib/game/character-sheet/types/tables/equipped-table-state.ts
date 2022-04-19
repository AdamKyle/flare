import InventoryDetails from "../inventory/inventory-details";

export default interface EquippedTableState {
    data: InventoryDetails[] | [];

    loading: boolean;

    search_string: string;

    success_message: string | null;
}
