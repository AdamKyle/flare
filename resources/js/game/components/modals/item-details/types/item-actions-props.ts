import {ComparisonData} from "../../../item-comparison/deffinitions/comparison-data";
import {UsableSets} from "./item-details-modal-state";
import InventoryDetails from "../../../../lib/game/character-sheet/types/inventory/inventory-details";

export default interface ItemActionsProps {
    slot_id: number;
    character_id: number;
    dark_charts: boolean;
    is_automation_running: boolean;
    is_dead: boolean;
    comparison_details: ComparisonData;
    usable_sets: UsableSets[]|[];

    manage_modal?: () => void;
    update_inventory?: (inventory: {[key: string]: InventoryDetails[]}) => void;
    set_success_message?: (message: string) => void;
}
