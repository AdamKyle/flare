import KingdomDetails from "../kingdom-details";

export default interface KingdomProps {

    close_details: () => void;

    kingdom: KingdomDetails;

    dark_tables: boolean;

    character_gold: number;

    view_port: number;

    user_id: number;
}
