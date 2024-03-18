import ComparisonDetails from "../deffinitions/comparison-details";

export default interface ItemComparisonState {
    expanded_comparison_details: ComparisonDetails | null;
    view_port: number;
}
