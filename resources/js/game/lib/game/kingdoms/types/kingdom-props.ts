import KingdomDetails from "../kingdom-details";

export default interface KingdomProps {

    close_details: () => void;

    kingdom: KingdomDetails;

    dark_tables: boolean;

    update_kingdoms: (kingdom: KingdomDetails) => void;
}
