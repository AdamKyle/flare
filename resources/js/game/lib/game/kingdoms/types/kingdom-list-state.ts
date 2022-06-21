import KingdomDetails from "../kingdom-details";

export default interface KingdomListState {

    loading: boolean;

    dark_tables: boolean;

    kingdoms: KingdomDetails[] | [],

    selected_kingdom: KingdomDetails | null;
}
