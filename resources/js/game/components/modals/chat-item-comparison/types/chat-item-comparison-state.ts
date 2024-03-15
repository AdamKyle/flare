import {ComparisonData} from "../../../item-comparison/deffinitions/comparison-data";

export interface UsableSets {
    index: number;
    id: number;
    name: string;
    equipped: boolean;
}

export interface ChatItemComparisonState {
    comparison_details: ComparisonData | null;
    usable_sets: UsableSets[] | [];
    action_loading: boolean;
    loading: boolean;
    dark_charts: boolean;
    error_message: string | null;
}
