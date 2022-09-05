import KingdomDetails from "../kingdom-details";
import KingdomLogDetails from "../kingdom-log-details";

export default interface KingdomListState {

    loading: boolean;

    dark_tables: boolean;

    selected_kingdom: KingdomDetails | null;

    selected_log: KingdomLogDetails | null;
}
