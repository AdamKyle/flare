import InventoryDetails from "../inventory/inventory-details";
import UsableItemsDetails from "../inventory/usable-items-details";

export default interface UsableItemTable {

    dark_table: boolean;

    usable_items: UsableItemsDetails[] | [];

    is_dead: boolean;

    character_id: number;

    update_inventory: (inventory: {[key: string]: InventoryDetails[]}) => void;

    set_success_message: (message: string) => void;

    is_automation_running: boolean;
}
