export default interface ResourceBuildingExpansionState {
    expanding: boolean;
    error_message: string | null;
    success_message: string | null;
    time_remaining_for_expansion: number;
}
