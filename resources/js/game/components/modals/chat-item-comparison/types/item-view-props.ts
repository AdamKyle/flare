
import {ComparisonData} from "../../../item-comparison/deffinitions/comparison-data";
import {UsableSets} from "./chat-item-comparison-state";

export default interface ItemViewProps {
    comparison_details: ComparisonData;
    manage_showing_expanded_section: () => void;
    is_showing_expanded_section: boolean;
    usable_sets: UsableSets[]|[]
}
