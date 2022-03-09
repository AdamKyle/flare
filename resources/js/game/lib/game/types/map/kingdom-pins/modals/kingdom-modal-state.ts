import KingdomDetails from "../../../../map/types/kingdom-details";

export default interface KingdomModalState {

    can_afford: boolean;

    distance: number;

    cost: number;

    time_out: number;

    x: number;

    y: number;

    loading: boolean;

    kingdom_details: KingdomDetails | null,
}
