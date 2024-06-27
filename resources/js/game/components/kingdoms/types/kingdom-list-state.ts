import KingdomDetails from "../deffinitions/kingdom-details";
import KingdomLogDetails from "../deffinitions/kingdom-log-details";

export default interface KingdomListState {
    loading: boolean;

    dark_tables: boolean;

    selected_kingdom: KingdomDetails | null;

    selected_log: KingdomLogDetails | null;

    already_has_capital_city: boolean;
}
