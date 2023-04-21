import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import SetDetails from "../../../../../lib/game/character-sheet/types/inventory/set-details";

export default interface InventoryItemComparisonProps {
    is_open: boolean;

    dark_charts: boolean;

    is_dead: boolean;

    manage_modal: () => void;

    title: string;

    slot_id: number;

    item_type: string;

    character_id: number;

    usable_sets: SetDetails[] | [];

    update_inventory: (inventory: {[key: string]: InventoryDetails[]}) => void;

    set_success_message: (message: string) => void;

    is_automation_running: boolean;
}
