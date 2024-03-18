
import {ComparisonData} from "../../../item-comparison/deffinitions/comparison-data";
import {UsableSets} from "./item-details-modal-state";
import InventoryDetails from "../../../../lib/game/character-sheet/types/inventory/inventory-details";

export default interface ItemViewProps {
    comparison_details: ComparisonData;
    manage_showing_expanded_section: () => void;
    is_showing_expanded_section: boolean;
    usable_sets: UsableSets[]|[];
    is_automation_running: boolean;
    is_dead: boolean;

    manage_modal?: () => void;
    update_inventory?: (inventory: {[key: string]: InventoryDetails[]}) => void;
    set_success_message?: (message: string) => void;
}
