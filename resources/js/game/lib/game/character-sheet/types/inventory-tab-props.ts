import InventoryDetails from "./inventory/inventory-details";
import SetDetails from "./inventory/set-details";

export default interface InventoryTabProps {

    inventory: InventoryDetails[];

    usable_sets: SetDetails[] | [];

    character_id: number;

    dark_table: boolean;

    is_dead: boolean;

    update_inventory: (inventory: {[key: string]: InventoryDetails[]}) => void;

    set_success_message: (message: string) => void;

    is_automation_running: boolean;
}
