import {ComparisonData} from "../../../item-comparison/deffinitions/comparison-data";
import {UsableSets} from "./chat-item-comparison-state";

export default interface ItemActionsProps {
    slot_id: number;
    character_id: number;
    dark_charts: boolean;
    is_automation_running: boolean;
    comparison_details: ComparisonData;
    usable_sets: UsableSets[]|[]
}
