import LocationModalState from "../../location-pins/modals/location-modal-state";

export default interface KingdomModalState extends LocationModalState {

    loading: boolean;

    show_purchase_modal?: boolean;

    title: string;

    npc_owned: boolean;

    action_in_progress: boolean;
}
