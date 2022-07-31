import KingdomDetails from "../../../../../map/types/kingdom-details";

export default interface KingdomDetailsProps {

    kingdom_id: number;

    character_id: number;

    update_loading: (kingdomDetails: KingdomDetails) => void;

    show_top_section: boolean;
}
