import InventoryDetails from "../inventory/inventory-details";

export default interface UsableItemTable {

    dark_table: boolean;

    usable_items: InventoryDetails[] | [];

    is_dead: boolean;
}
