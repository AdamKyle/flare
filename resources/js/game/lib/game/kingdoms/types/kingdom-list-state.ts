import KingdomDetails from "../kingdom-details";

export default interface KingdomListState {

    loading: boolean;

    dark_tables: boolean;

    selected_kingdom: KingdomDetails | null;
}
