import {ComparisonData} from "../deffinitions/comparison-data";

export default interface ItemComparisonProps {
    comparison_info: ComparisonData;
    is_showing_expanded_comparison: boolean;
    manage_show_expanded_comparison: () => void;
}
