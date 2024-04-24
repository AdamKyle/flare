import KingdomDetails from "../deffinitions/kingdom-details";

export default interface SmallKingdomState {
    show_kingdom_details: boolean;

    which_selected: string|null;

    kingdom: KingdomDetails | null;

    loading: boolean;

    error_message: string | null;
}
