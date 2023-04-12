import ComparisonDetails from '../definitions/comparison-details';
import SetDetails from "../../../../../lib/game/character-sheet/types/inventory/set-details";
import InventoryDetails from "../../../../../lib/game/character-sheet/types/inventory/inventory-details";
import InventoryComparisonAdjustment from "../definitions/inventory-comparison-adjustment";

export default interface ComparisonSectionProps {
    is_large_modal: boolean;

    is_grid_size: (size: number, itemToEquip: InventoryComparisonAdjustment) => boolean;

    comparison_details: ComparisonDetails;

    set_action_loading: () => void;

    is_action_loading: boolean;

    manage_modal: () => void;

    character_id: number;

    dark_charts: boolean;

    usable_sets: SetDetails[] | [];

    slot_id: number;

    is_automation_running: boolean;

    update_inventory?: (inventory: {[key: string]: InventoryDetails[]}) => void;

    set_success_message?: (message: string) => void;
}
