export default interface BuildingInformationState {
    upgrade_section: string | null;
    success_message: string;
    error_message: string;
    loading: boolean;
    to_level: number;
}
