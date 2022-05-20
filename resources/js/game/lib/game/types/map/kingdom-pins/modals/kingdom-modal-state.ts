import KingdomDetails from "../../../../map/types/kingdom-details";
import LocationModalState from "../../location-pins/modals/location-modal-state";

export default interface KingdomModalState extends LocationModalState {

    loading: boolean;

    kingdom_details: KingdomDetails | null,

    show_help: boolean;

    help_type: string;
}
