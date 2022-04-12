import InventoryDetails from "../inventory/inventory-details";
import SetDetails from "../inventory/set-details";

export default interface InventoryItemComparisonProps {
    is_open: boolean;

    manage_modal: () => void;

    title: string;

    slot_id: number;

    item_type: string;

    character_id: number;

    usable_sets: SetDetails[] | [];

    update_inventory: (inventory: {[key: string]: InventoryDetails[]}) => void;

    set_success_message: (message: string) => void;
}
