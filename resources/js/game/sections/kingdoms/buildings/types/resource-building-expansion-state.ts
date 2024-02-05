import KingdomBuildingExpansion from "../deffinitions/kingdom-building-expansion";

export default interface ResourceBuildingExpansionState {
    loading: boolean;
    expanding: boolean;
    error_message: string | null;
    success_message: string | null;
    time_remaining_for_expansion: number;
    expansion_details: KingdomBuildingExpansion | null;
}
