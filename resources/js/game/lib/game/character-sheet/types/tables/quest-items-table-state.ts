import InventoryDetails from "../inventory/inventory-details";

export default interface QuestItemsTableState {
    data: InventoryDetails[] | [];

    item_id: number | null;

    view_item: boolean;
}
