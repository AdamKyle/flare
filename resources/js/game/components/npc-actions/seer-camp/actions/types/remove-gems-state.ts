import {
    ComparisonData,
    GemsForComparison,
} from "../deffinitions/gems-removal-comparison";
import Items from "../deffinitions/items";

export default interface RemoveGemsState {
    fetching_data: boolean;
    removing_gem: boolean;
    selected_item: number;
    items: Items[] | [];
    gems: SelectedGemData[] | [];
    selected_gem_data: SelectedGemData | null;
}

interface SelectedGemData {
    comparison: ComparisonData;
    gems: GemsForComparison;
    slot_id: number;
}
