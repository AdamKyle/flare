import InventoryDetails from "../inventory/inventory-details";

export default interface EquippedTableState {
    data: InventoryDetails[] | [];

    loading: boolean;
}
