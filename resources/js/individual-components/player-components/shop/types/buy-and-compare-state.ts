import {ComparisonData} from "../../../../game/components/item-comparison/deffinitions/comparison-data";

export default interface BuyAndCompareState {
    loading: boolean,
    comparison_data: ComparisonData | null,
    error_message: string | null,
    success_message: string | null,
    is_showing_expanded_comparison: boolean,
}
