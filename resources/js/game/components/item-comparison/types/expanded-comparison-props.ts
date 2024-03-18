import ComparisonDetails from "../deffinitions/comparison-details";

export default interface ExpandedComparisonProps {
    comparison_details: ComparisonDetails;
    mobile_data?: {
        view_port: number,
        mobile_height_restriction?: boolean
    }
}
